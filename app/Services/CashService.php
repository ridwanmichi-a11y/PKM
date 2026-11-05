<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\Audit;
use App\Support\Fmt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Semua perubahan buku kas lewat sini, supaya aturan akuntabilitas selalu berlaku:
 * nomor bukti otomatis, nama pencatat, persetujuan Owner, pembatalan beralasan, jejak audit.
 */
class CashService
{
    public static function approvalLimit(): int
    {
        return (int) Setting::get('approval_limit', 500000);
    }

    /** Catat pembayaran pesanan (DP / pelunasan) sebagai kas masuk. */
    public static function recordPayment(Order $order, int $amount, string $account, User $by, $date = null, ?UploadedFile $proof = null): CashTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
        }
        if ($amount > $order->remaining()) {
            throw ValidationException::withMessages(['amount' => 'Jumlah melebihi sisa tagihan ('.Fmt::rp($order->remaining()).').']);
        }

        return DB::transaction(function () use ($order, $amount, $account, $by, $date, $proof) {
            $date = $date ? Carbon::parse($date) : Fmt::today();
            $isFirst = $order->paid === 0;
            $settles = $order->paid + $amount >= $order->total;
            $label = $settles ? ($isFirst ? 'Pembayaran lunas ' : 'Pelunasan ') : ($isFirst ? 'DP ' : 'Cicilan ');

            $tx = CashTransaction::create([
                'code' => CodeGenerator::cash('in', $date),
                'type' => 'in',
                'date' => $date->toDateString(),
                'category' => 'Pembayaran pesanan',
                'description' => $label.$order->code.' · '.$order->customer->name,
                'amount' => $amount,
                'account' => $account,
                'order_id' => $order->id,
                'status' => 'tercatat',
                'created_by' => $by->id,
            ] + static::storeReceipt($proof));

            $order->refreshPaid();
            Audit::log('payment', $tx, "Mencatat {$label}{$order->code} sebesar ".Fmt::rp($amount).' ('.$tx->accountLabel().')');

            return $tx;
        });
    }

    /** Kas masuk/keluar manual dari menu Kas. */
    public static function create(array $data, User $by, ?UploadedFile $receipt = null): CashTransaction
    {
        return DB::transaction(function () use ($data, $by, $receipt) {
            $date = Carbon::parse($data['date']);
            $needsApproval = $data['type'] === 'out'
                && ! $by->isOwner()
                && (int) $data['amount'] > static::approvalLimit();

            $tx = CashTransaction::create([
                'code' => CodeGenerator::cash($data['type'], $date),
                'type' => $data['type'],
                'date' => $date->toDateString(),
                'category' => $data['category'],
                'description' => $data['description'],
                'amount' => (int) $data['amount'],
                'account' => $data['account'],
                'status' => $needsApproval ? 'menunggu' : 'tercatat',
                'created_by' => $by->id,
                'approved_by' => $by->isOwner() ? $by->id : null,
                'approved_at' => $by->isOwner() ? Fmt::now() : null,
            ] + static::storeReceipt($receipt));

            Audit::log('create', $tx, 'Mencatat '.($tx->isIn() ? 'kas masuk' : 'kas keluar').' "'.$tx->description.'" '.Fmt::rp($tx->amount)
                .($needsApproval ? ' — menunggu persetujuan Owner' : ''));

            return $tx;
        });
    }

    public static function approve(CashTransaction $tx, User $by): void
    {
        static::ensureStatus($tx, 'menunggu');
        $tx->update(['status' => 'tercatat', 'approved_by' => $by->id, 'approved_at' => Fmt::now()]);
        Audit::log('approve', $tx, 'Menyetujui '.$tx->code.' "'.$tx->description.'" '.Fmt::rp($tx->amount));
    }

    public static function reject(CashTransaction $tx, User $by, string $reason): void
    {
        static::ensureStatus($tx, 'menunggu');
        $tx->update(['status' => 'ditolak', 'approved_by' => $by->id, 'approved_at' => Fmt::now(), 'reject_reason' => $reason]);
        Audit::log('reject', $tx, 'Menolak '.$tx->code.' · alasan: '.$reason);
    }

    /** Transaksi tidak pernah dihapus; hanya dibatalkan dengan alasan. */
    public static function void(CashTransaction $tx, User $by, string $reason): void
    {
        static::ensureStatus($tx, 'tercatat');
        DB::transaction(function () use ($tx, $by, $reason) {
            $tx->update(['status' => 'batal', 'voided_by' => $by->id, 'voided_at' => Fmt::now(), 'void_reason' => $reason]);
            $tx->order?->refreshPaid();
            Audit::log('void', $tx, 'Membatalkan '.$tx->code.' '.Fmt::rp($tx->amount).' · alasan: '.$reason);
        });
    }

    private static function ensureStatus(CashTransaction $tx, string $status): void
    {
        if ($tx->status !== $status) {
            throw ValidationException::withMessages(['status' => 'Transaksi '.$tx->code.' berstatus "'.$tx->statusLabel().'", tindakan ini tidak bisa dilakukan.']);
        }
    }

    private static function storeReceipt(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        return [
            'receipt_path' => $file->store('nota-kas', 'local'),
            'receipt_name' => $file->getClientOriginalName(),
        ];
    }
}
