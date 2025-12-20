@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Tambah Penjualan</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Buat transaksi penjualan baru</p>
        </div>
    </div>

    <form id="saleForm" action="{{ route('penjualan.store') }}" method="POST" class="card"
        style="max-width:960px; gap:14px;">
        @csrf
        @if(isset($parentSaleId))
            <input type="hidden" name="parent_sale_id" value="{{ $parentSaleId }}">
        @endif

        <h3 class="panel-title">Data Utama</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Kavling (Tersedia)</label>
                <select name="lot_id" class="input" id="lotSelect" required>
                    <option value="">Pilih Kavling</option>
                    @foreach($lots as $lot)
                        <option value="{{ $lot->id }}" data-base-price="{{ $lot->base_price }}"
                            data-project="{{ optional($lot->project)->name }}" data-area="{{ $lot->area }}">
                            {{ optional($lot->project)->name }} / {{ $lot->block_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Pelanggan</label>
                <select name="buyer_id" class="input" required>
                    <option value="">Pilih Pelanggan</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}" {{ (isset($buyerId) && $buyerId == $buyer->id) ? 'selected' : '' }}>
                            {{ $buyer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Sales</label>
                <select name="marketer_id" class="input">
                    <option value="">Pilih Sales</option>
                    @foreach($marketers as $marketer)
                        <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Metode Pembayaran</label>
                <select name="payment_method" class="input" id="paymentMethod" required>
                    <option value="cash">Cash Keras</option>
                    <option value="installment">Angsuran In-house</option>
                    <option value="kpr">KPR Bank</option>
                </select>
            </div>
            <div class="field">
                <label class="hint">Tgl Booking</label>
                <input class="input" type="date" name="booking_date" required>
            </div>
        </div>

        <h3 class="panel-title">Detail Harga</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Harga Dasar (Rp)</label>
                <input class="input" type="number" name="base_price" id="basePrice" min="0" required
                    value="{{ old('base_price', '') }}" placeholder="Masukkan harga dasar">
            </div>
            <div class="field">
                <label class="hint">Promo/Diskon (Rp)</label>
                <input class="input" type="number" name="discount" id="discount" min="0" value="{{ old('discount', '') }}"
                    placeholder="Diskon/promo (opsional)">
            </div>
            <div class="field">
                <label class="hint">Harga Netto (Rp)</label>
                <input class="input readonly" type="number" name="price" id="netPrice" min="0"
                    value="{{ old('price', '') }}" placeholder="Otomatis dihitung" readonly>
            </div>
        </div>

        <h3 class="panel-title">Biaya Tambahan</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Biaya PPJB (Rp)</label>
                <input class="input" type="number" name="extra_ppjb" id="extraPpjb" min="0"
                    value="{{ old('extra_ppjb', '') }}" placeholder="Isi jika ada biaya PPJB">
            </div>
            <div class="field">
                <label class="hint">Biaya SHM (Rp)</label>
                <input class="input" type="number" name="extra_shm" id="extraShm" min="0" value="{{ old('extra_shm', '') }}"
                    placeholder="Isi jika ada biaya SHM">
            </div>
            <div class="field">
                <label class="hint">Biaya Lain (Rp)</label>
                <input class="input" type="number" name="extra_other" id="extraOther" min="0"
                    value="{{ old('extra_other', '') }}" placeholder="Biaya lain (opsional)">
            </div>
            <div class="field">
                <label class="hint">Grand Total (Rp)</label>
                <input class="input readonly" type="number" id="grandTotal" min="0" value="{{ old('price', '') }}"
                    placeholder="Otomatis dihitung" readonly>
            </div>
        </div>

        <h3 class="panel-title">Skema Pembayaran</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Tenor (bulan)</label>
                <input class="input" type="number" name="tenor_months" id="tenorInput" min="1" required
                    value="{{ old('tenor_months') ?: '' }}" placeholder="Misal: 12, 24, 36">
            </div>
            <div class="field">
                <label class="hint">Tanggal Jatuh Tempo (1-28)</label>
                <input class="input" type="number" name="due_day" id="dueDayInput" min="1" max="28" required
                    value="{{ old('due_day', '') }}" placeholder="1 - 28">
            </div>
        </div>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Uang Muka (%)</label>
                <input class="input" type="number" name="dp_percent" id="dpPercentInput" min="0" max="100"
                    value="{{ old('dp_percent') ?: '' }}" placeholder="Misal: 10, 20, 30">
            </div>
            <div class="field">
                <label class="hint">Uang Muka (Rp)</label>
                <input class="input" type="number" name="down_payment" id="dpInput" min="0"
                    value="{{ old('down_payment') ?: '' }}" placeholder="Masukkan nominal DP">
                <small class="hint" id="dpPercent">0% dari harga</small>
            </div>
        </div>
        <div class="field">
            <label class="hint">Estimasi Angsuran / Bulan</label>
            <input class="input readonly" type="text" id="installmentEstimate" value="Rp 0" readonly>
        </div>

        <div class="field">
            <label class="hint">Catatan Transaksi</label>
            <textarea class="input" name="notes" rows="3"
                placeholder="Catatan tambahan untuk transaksi ini (opsional)">{{ old('notes', '') }}</textarea>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('penjualan.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan</button>
        </div>
    </form>

    @push('scripts')
        <script>
            const basePrice = document.getElementById('basePrice');
            const discount = document.getElementById('discount');
            const netPrice = document.getElementById('netPrice');
            const extraPpjb = document.getElementById('extraPpjb');
            const extraShm = document.getElementById('extraShm');
            const extraOther = document.getElementById('extraOther');
            const grandTotal = document.getElementById('grandTotal');
            const dpPercentInput = document.getElementById('dpPercentInput');
            const priceInput = netPrice; // for compatibility
            const dpInput = document.getElementById('dpInput');
            const tenorInput = document.getElementById('tenorInput');
            const paymentMethod = document.getElementById('paymentMethod');
            const dpPercentEl = document.getElementById('dpPercent');
            const installmentEstimate = document.getElementById('installmentEstimate');
            const dueDayInput = document.getElementById('dueDayInput');
            const lotSelect = document.getElementById('lotSelect');
            window.lastDpChange = null;

            function formatIDR(n) {
                return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
            }

            const isEmptyOrZero = (val) => val === '' || val === null || Number(val) === 0;

            function hydrateFromLot() {
                const selected = lotSelect?.selectedOptions?.[0];
                if (!selected) return;
                const base = Number(selected.getAttribute('data-base-price') || 0);
                if (base > 0) {
                    basePrice.value = base;
                }
                recalc();
            }

            async function autoFetchLotPricing(lotId, { forceDpDefaults = false, preserveExisting = false } = {}) {
                if (!lotId) return;
                try {
                    const res = await fetch(`/kavling/${lotId}/pricing`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (Number.isFinite(Number(data?.base_price)) && (!preserveExisting || isEmptyOrZero(basePrice.value))) {
                        basePrice.value = data.base_price;
                    }
                    // Only fill installment defaults if payment method is installment
                    const isInstallment = paymentMethod.value === 'installment';
                    if (isInstallment) {
                        const defaults = data?.payment_defaults || {};
                        if ((forceDpDefaults || isEmptyOrZero(dpPercentInput.value)) && Number.isFinite(Number(defaults.dp_percent))) {
                            dpPercentInput.value = defaults.dp_percent;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(dpInput.value)) && Number.isFinite(Number(defaults.dp_nominal))) {
                            dpInput.value = defaults.dp_nominal;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(tenorInput.value)) && Number.isFinite(Number(defaults.tenor_months))) {
                            tenorInput.value = defaults.tenor_months;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(dueDayInput.value)) && Number.isFinite(Number(defaults.due_day))) {
                            dueDayInput.value = defaults.due_day;
                        }
                    }
                } catch (e) {
                    // noop fallback to local data attributes
                }
                recalc();
            }

            function recalc() {
                const base = Number(basePrice.value || 0);
                const disc = Number(discount.value || 0);
                const ppjb = Number(extraPpjb.value || 0);
                const shm = Number(extraShm.value || 0);
                const oth = Number(extraOther.value || 0);

                const net = Math.max(0, base - disc + ppjb + shm + oth);
                netPrice.value = net;
                grandTotal.value = net;

                // Handle Cash Keras & KPR Bank - disable tenor, due day, and DP fields
                // Both are full payment to developer (no installments from developer's perspective)
                const isFullPayment = paymentMethod.value === 'cash' || paymentMethod.value === 'kpr';
                const paymentLabel = paymentMethod.value === 'cash' ? 'Cash Keras' : 'KPR Bank';

                if (isFullPayment) {
                    tenorInput.value = '';
                    tenorInput.disabled = true;
                    dueDayInput.value = '';
                    dueDayInput.disabled = true;
                    dpPercentInput.value = '';
                    dpPercentInput.disabled = true;
                    dpInput.value = '';
                    dpInput.disabled = true;
                    tenorInput.required = false;
                    dueDayInput.required = false;
                    dpPercentEl.textContent = `${paymentLabel} - pembayaran penuh`;
                    installmentEstimate.value = `N/A (${paymentLabel})`;
                    return; // No need to calculate DP/installments
                }

                // Re-enable fields for Installment only
                tenorInput.disabled = false;
                dueDayInput.disabled = false;
                dpPercentInput.disabled = false;
                dpInput.disabled = false;
                tenorInput.required = true;
                dueDayInput.required = true;

                const dpPercentVal = Number(dpPercentInput.value || 0);
                const dpInputVal = Number(dpInput.value || 0);
                let dp = dpInputVal;

                if (window.lastDpChange === 'percent') {
                    dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                    dpInput.value = dp;
                } else if (window.lastDpChange === 'nominal') {
                    const pct = net > 0 ? (dp / net) * 100 : 0;
                    dpPercentInput.value = pct ? Number(pct.toFixed(2)) : 0;
                } else {
                    dp = dpInputVal || Math.round(net * (dpPercentVal / 100));
                    dpInput.value = dp;
                }
                const tenor = Number(tenorInput.value || 0);

                const percent = net > 0 ? Math.round((dp / net) * 100) : 0;
                dpPercentEl.textContent = `${percent}% dari harga`;

                const outstanding = Math.max(0, net - dp);
                const monthly = (tenor > 0 && paymentMethod.value === 'installment') ? Math.ceil(outstanding / tenor) : outstanding;
                installmentEstimate.value = formatIDR(monthly);
            }

            [basePrice, discount, extraPpjb, extraShm, extraOther, dpPercentInput, dpInput, tenorInput, paymentMethod].forEach(el => el?.addEventListener('input', recalc));
            dpPercentInput?.addEventListener('input', () => { window.lastDpChange = 'percent'; recalc(); });
            dpInput?.addEventListener('input', () => { window.lastDpChange = 'nominal'; recalc(); });
            [basePrice, discount, extraPpjb, extraShm, extraOther, tenorInput, paymentMethod].forEach(el => el?.addEventListener('input', () => { if (!['percent', 'nominal'].includes(window.lastDpChange)) window.lastDpChange = null; recalc(); }));
            lotSelect?.addEventListener('change', () => {
                window.lastDpChange = null;
                hydrateFromLot();
                autoFetchLotPricing(lotSelect.value, { forceDpDefaults: true });
            });

            // Prefill on first load using DB data when available
            hydrateFromLot();
            autoFetchLotPricing(lotSelect?.value || '');
            recalc();
        </script>
    @endpush
@endsection
