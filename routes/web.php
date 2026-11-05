<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SipzStore — Aplikasi Keuangan & Pesanan
|--------------------------------------------------------------------------
| Peran: owner (semua), admin (pesanan, pelanggan, kas), operator (produksi)
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'role'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    // Produksi (operator & owner)
    Route::get('/produksi', [ProductionController::class, 'index'])->name('production.index')->middleware('role:owner,operator,admin');

    // Pesanan — semua peran boleh melihat; aturan per langkah dicek di layanan.
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show')->whereNumber('order');
    Route::patch('/pesanan/{order}/status', [OrderController::class, 'advance'])->name('orders.advance');
    Route::get('/pesanan/{order}/desain', [OrderController::class, 'design'])->name('orders.design');

    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/pesanan/baru', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/pesanan', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/pesanan/{order}/ubah', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/pesanan/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::post('/pesanan/{order}/bayar', [OrderController::class, 'pay'])->name('orders.pay');
        Route::patch('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/pesanan/{order}/nota', [OrderController::class, 'invoice'])->name('orders.invoice');

        Route::resource('pelanggan', CustomerController::class)
            ->parameters(['pelanggan' => 'customer'])
            ->names('customers');

        Route::get('/kas', [CashController::class, 'index'])->name('cash.index');
        Route::get('/kas/baru', [CashController::class, 'create'])->name('cash.create');
        Route::post('/kas', [CashController::class, 'store'])->name('cash.store');
        Route::get('/kas/ekspor', [CashController::class, 'export'])->name('cash.export');
        Route::get('/kas/{transaction}/nota', [CashController::class, 'receipt'])->name('cash.receipt');
    });

    // Khusus Owner
    Route::middleware('role:owner')->group(function () {
        Route::post('/kas/{transaction}/setujui', [CashController::class, 'approve'])->name('cash.approve');
        Route::post('/kas/{transaction}/tolak', [CashController::class, 'reject'])->name('cash.reject');
        Route::post('/kas/{transaction}/batal', [CashController::class, 'void'])->name('cash.void');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/cetak', [ReportController::class, 'print'])->name('reports.print');
        Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

        Route::resource('produk', ProductController::class)
            ->except(['show', 'destroy'])
            ->parameters(['produk' => 'product'])
            ->names('products');

        Route::resource('pengguna', UserController::class)
            ->except(['show', 'destroy'])
            ->parameters(['pengguna' => 'user'])
            ->names('users');

        Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    });
});
