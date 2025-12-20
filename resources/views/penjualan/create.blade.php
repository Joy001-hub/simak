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
                <label class="hint">Kavling (Tersedia) <span style="color:red">*</span></label>
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
                <label class="hint">Pelanggan <span style="color:red">*</span></label>
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
                <label class="hint">Sales <span style="color:red">*</span></label>
                <select name="marketer_id" class="input" required>
                    <option value="">Pilih Sales</option>
                    @foreach($marketers as $marketer)
                        <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Metode Pembayaran <span style="color:red">*</span></label>
                <select name="payment_method" class="input" id="paymentMethod" required>
                    <option value="">Pilih Metode Pembayaran</option>
                    <option value="cash">Cash Keras</option>
                    <option value="installment">Angsuran In-house</option>
                    <option value="kpr">KPR Bank</option>
                </select>
            </div>
            <div class="field">
                <label class="hint">Tgl Booking <span style="color:red">*</span></label>
                <input class="input" type="date" name="booking_date" required>
            </div>
        </div>

        <h3 class="panel-title">Detail Harga</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Harga Dasar (Rp) <span style="color:red">*</span></label>
                <input class="input" type="number" name="base_price" id="basePrice" min="0"
                    value="{{ old('base_price', '') }}" placeholder="Masukkan harga dasar" required>
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
            <div class="field" id="tenorField">
                <label class="hint">Tenor (bulan) <span class="req-mark" style="color:red">*</span></label>
                <input class="input" type="number" name="tenor_months" id="tenorInput" min="0"
                    value="{{ old('tenor_months') ?: '' }}" placeholder="Misal: 12, 24, 36">
            </div>
            <div class="field" id="dueDayField">
                <label class="hint">Tanggal Jatuh Tempo (1-31) <span class="req-mark" style="color:red">*</span></label>
                <input class="input" type="number" name="due_day" id="dueDayInput" min="1" max="31"
                    value="{{ old('due_day', '') }}" placeholder="1 - 31">
            </div>
        </div>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Uang Muka (%)</label>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input class="input" type="number" name="dp_percent" id="dpPercentInput" min="0" max="100" step="any"
                        value="{{ old('dp_percent') ?: '' }}" placeholder="Misal: 10, 20, dst (opsional)" style="flex:1;">
                    <span style="color:#64748B; font-size:13px; white-space:nowrap;">/ <strong id="dpRupiahDisplay">Rp
                            0</strong></span>
                </div>
                <input type="hidden" name="down_payment" id="dpInput" value="{{ old('down_payment') ?: '' }}">
            </div>
            <div class="field">
                <label class="hint">Booking Fee (Rp)</label>
                <input class="input" type="number" name="booking_fee" id="bookingFeeInput" min="0"
                    value="{{ old('booking_fee', '') }}" placeholder="Booking Fee (opsional)">
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
            const dpRupiahDisplay = document.getElementById('dpRupiahDisplay');
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


                // Handle Cash Keras - full payment, disable all installment fields
                if (paymentMethod.value === 'cash') {
                    tenorInput.value = '';
                    tenorInput.disabled = true;
                    tenorInput.removeAttribute('required');
                    dueDayInput.value = '';
                    dueDayInput.disabled = true;
                    dueDayInput.removeAttribute('required');
                    dpPercentInput.value = '';
                    dpPercentInput.disabled = true;
                    dpInput.value = '';
                    // Hide asterisks for tenor/due day
                    document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'none');
                    if (dpRupiahDisplay) dpRupiahDisplay.textContent = 'N/A (Cash Keras)';
                    installmentEstimate.value = 'N/A (Cash Keras)';
                    return;
                }

                // Handle KPR Bank - allow DP optional, disable tenor/due day
                if (paymentMethod.value === 'kpr') {
                    tenorInput.value = '';
                    tenorInput.disabled = true;
                    tenorInput.removeAttribute('required');
                    dueDayInput.value = '';
                    dueDayInput.disabled = true;
                    dueDayInput.removeAttribute('required');
                    // Hide asterisks for tenor/due day
                    document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'none');
                    installmentEstimate.value = 'N/A (KPR Bank)';
                    // DP is optional for KPR
                    dpPercentInput.disabled = false;
                    const dpPercentVal = Number(dpPercentInput.value || 0);
                    const dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                    dpInput.value = dp;
                    if (dpRupiahDisplay) dpRupiahDisplay.textContent = formatIDR(dp);
                    return;
                }

                // Re-enable all fields for Installment
                tenorInput.disabled = false;
                tenorInput.setAttribute('required', 'required');
                dueDayInput.disabled = false;
                dueDayInput.setAttribute('required', 'required');
                dpPercentInput.disabled = false;
                // Show asterisks for required fields
                document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'inline');

                // Calculate DP from percentage only
                const dpPercentVal = Number(dpPercentInput.value || 0);
                const dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                dpInput.value = dp;

                // Display rupiah value next to percentage input
                if (dpRupiahDisplay) dpRupiahDisplay.textContent = formatIDR(dp);

                const tenor = Number(tenorInput.value || 0);
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