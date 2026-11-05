/* SipzStore — interaksi kecil tanpa library */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var rupiah = function (n) { return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID'); };
  var toInt = function (v) { return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0; };

  /* Konfirmasi sebelum aksi penting: <form data-confirm="..."> */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* Minta alasan sebelum kirim: <form data-prompt="..." data-prompt-field="reason"> */
  $$('form[data-prompt]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      var field = f.elements[f.getAttribute('data-prompt-field') || 'reason'];
      var answer = window.prompt(f.getAttribute('data-prompt'), '');
      if (!answer || !answer.trim()) { e.preventDefault(); return; }
      field.value = answer.trim();
    });
  });

  /* Format angka rupiah saat diketik: <input data-money> */
  $$('input[data-money]').forEach(function (inp) {
    var fmt = function () {
      var n = toInt(inp.value);
      inp.value = n ? n.toLocaleString('id-ID') : '';
    };
    inp.addEventListener('input', fmt);
    fmt();
  });

  /* Nama file terpilih: <label class="dropzone"><input type="file" data-file-label="#id"> */
  $$('input[type=file][data-file-label]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var target = $(inp.getAttribute('data-file-label'));
      if (target) target.textContent = inp.files.length ? 'Dipilih: ' + Array.prototype.map.call(inp.files, function (f) { return f.name; }).join(', ') : '';
    });
  });

  /* Kirim form saat pilihan berubah: <select data-autosubmit> */
  $$('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  /* ---------- Form pesanan ---------- */
  var form = $('#order-form');
  if (!form) return;

  var body = $('#items-body');
  var tpl = $('#item-template');
  var totalEl = $('[data-total]');
  var restEl = $('[data-rest]');
  var payInput = form.elements['pay_amount'];
  var qtyEl = $('[data-total-qty]');

  function recalc() {
    var total = 0, qty = 0;
    $$('tr[data-item]', body).forEach(function (tr) {
      var q = toInt($('input[data-qty]', tr).value);
      var p = toInt($('input[data-price]', tr).value);
      $('[data-subtotal]', tr).textContent = rupiah(q * p);
      total += q * p;
      qty += q;
    });
    if (totalEl) totalEl.textContent = rupiah(total);
    if (qtyEl) qtyEl.textContent = qty.toLocaleString('id-ID') + ' pcs';
    autoPay(total);
    var paid = payInput ? toInt(payInput.value) : 0;
    if (restEl) restEl.textContent = rupiah(Math.max(0, total - paid));
    form.dataset.sum = total;
  }

  // Isi otomatis jumlah bayar sesuai pilihan (DP 50% / lunas) selama belum diketik manual.
  var payManual = payInput && payInput.value !== '';
  function autoPay(total) {
    if (!payInput || payManual) return;
    var mode = form.querySelector('input[name=pay_mode]:checked');
    if (!mode) return;
    var v = mode.value === 'dp' ? Math.round(total / 2 / 1000) * 1000 : (mode.value === 'full' ? total : 0);
    payInput.value = v ? v.toLocaleString('id-ID') : '';
  }

  function wire(tr) {
    var sel = $('select[data-product]', tr);
    var name = $('input[data-name]', tr);
    var price = $('input[data-price]', tr);
    sel.addEventListener('change', function () {
      var opt = sel.options[sel.selectedIndex];
      if (opt && opt.value) {
        price.value = toInt(opt.getAttribute('data-unit-price')).toLocaleString('id-ID');
        name.value = opt.getAttribute('data-product-name');
      }
      recalc();
    });
    price.addEventListener('input', function () {
      var n = toInt(price.value);
      price.value = n ? n.toLocaleString('id-ID') : '';
    });
    $$('input', tr).forEach(function (i) { i.addEventListener('input', recalc); });
    var del = $('[data-remove]', tr);
    if (del) del.addEventListener('click', function () {
      if ($$('tr[data-item]', body).length > 1) { tr.remove(); renumber(); recalc(); }
    });
  }

  function renumber() {
    $$('tr[data-item]', body).forEach(function (tr, i) {
      $$('[name]', tr).forEach(function (el) {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
      });
    });
  }

  $$('tr[data-item]', body).forEach(wire);
  var add = $('[data-add-item]');
  if (add) add.addEventListener('click', function () {
    var tr = tpl.content.firstElementChild.cloneNode(true);
    body.appendChild(tr);
    renumber();
    wire(tr);
    $('select[data-product]', tr).focus();
  });

  // Pilihan pembayaran awal
  $$('input[name=pay_mode]').forEach(function (r) {
    r.addEventListener('change', function () {
      payManual = false;
      $('#pay-extra').hidden = r.value === 'none';
      recalc();
    });
  });
  if (payInput) payInput.addEventListener('input', function () { payManual = true; recalc(); });

  // Pelanggan baru / lama
  var custSel = form.elements['customer_id'];
  var newBox = $('#new-customer');
  if (custSel && newBox) {
    var sync = function () { newBox.hidden = !!custSel.value; };
    custSel.addEventListener('change', function () {
      sync();
      // Muat ulang untuk menampilkan riwayat pesanan pelanggan (hanya saat membuat pesanan baru).
      if (custSel.value && form.dataset.mode === 'create' && !form.dataset.dirty) {
        window.location = form.dataset.createUrl + '?pelanggan=' + custSel.value;
      }
    });
    sync();
  }
  form.addEventListener('input', function (e) {
    if (e.target.closest('#items-body')) form.dataset.dirty = '1';
  });

  recalc();
})();
