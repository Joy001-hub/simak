@extends('layouts.app')

@section('content')
    <div class="page-heading" style="align-items:center;">
        <div>
            <a href="{{ route('penjualan.index') }}" class="chip ghost" style="padding:6px 10px;">&larr; Kembali</a>
            <h1 class="heading-title" style="margin-top:8px;">Detail Penjualan</h1>
            <p class="heading-sub">Invoice: {{ $penjualan['invoice'] }}</p>
        </div>
        <div class="filter-row">
            <button id="sendReminder" class="chip ghost" style="border:1px solid #22c55e; color:#166534;">Kirim Tagihan</button>
            <a href="{{ route('penjualan.edit', $sale) }}" class="chip ghost" style="border:1px solid #eab308; color:#854d0e;">Edit Transaksi</a>
            @if ($sale->status === 'active' || $sale->status === 'paid_off')
            <button id="cancelSaleBtn" class="chip ghost" type="button" style="border:1px solid #b4232a; color:#b4232a;">Batalkan Penjualan</button>
            @else
            <span class="chip ghost" style="background:#fee2e2; color:#b91c1c; border:none;">Dibatalkan: {{ str_replace('_', ' ', str_replace('DIBATALKAN_', '', $sale->status)) }}</span>
            @endif
        </div>
    </div>

    <div class="panel-grid" style="grid-template-columns: 1.4fr 1fr; align-items:start;">
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="card" style="gap:12px;">
                <h3 class="panel-title" style="padding:0 0 6px 0;">Informasi Transaksi</h3>
                <div class="grid-2" style="column-gap:18px;">
                    <div class="field">
                        <span class="hint">Tgl. Booking</span>
                        <span class="stat-value" style="font-size:16px;">{{ $penjualan['tgl_booking'] }}</span>
                    </div>
                    <div class="field">
                        <span class="hint">Metode Bayar</span>
                        <span class="stat-value" style="font-size:16px;">{{ $penjualan['metode_bayar'] }}</span>
                    </div>
                    <div class="field">
                        <span class="hint">Pembeli</span>
                        <span class="stat-value" style="font-size:16px;">{{ $penjualan['pembeli'] }}</span>
                    </div>
                    <div class="field">
                        <span class="hint">Kavling</span>
                        <span class="stat-value" style="font-size:16px;">{{ $penjualan['kavling'] }}</span>
                    </div>
                    <div class="field">
                        <span class="hint">Marketing</span>
                        <span class="stat-value" style="font-size:16px;">{{ $penjualan['marketing'] ?? '-' }}</span>
                    </div>
                </div>
                <div class="field" style="margin-top:12px; padding-top:12px; border-top: 1px solid #e5e7eb;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="hint">Catatan</span>
                        <button type="button" id="editNotesBtn" class="btn light" style="padding:4px 10px; font-size:12px;">
                            <span id="editNotesBtnText">Edit</span>
                        </button>
                    </div>
                    <div id="notesDisplay" style="margin-top:6px;">
                        <span class="stat-value" style="font-size:14px; color:#374151;">{{ $sale->notes ?: '-' }}</span>
                    </div>
                    <form id="notesEditForm" action="{{ route('penjualan.updateNotes', $sale) }}" method="POST" style="display:none; margin-top:6px;">
                        @csrf
                        @method('PATCH')
                        <textarea name="notes" class="input" rows="3" style="font-size:14px;">{{ $sale->notes }}</textarea>
                        <div style="display:flex; gap:8px; margin-top:8px; justify-content:flex-end;">
                            <button type="button" id="cancelNotesEdit" class="btn light" style="padding:6px 12px;">Batal</button>
                            <button type="submit" class="btn primary" style="padding:6px 12px;">Simpan</button>
                        </div>
                    </form>
                </div>
                @if($sale->status_before_cancel)
                <div class="field" style="margin-top:8px; padding:8px 12px; background:#fef3c7; border-radius:8px;">
                    <span class="hint" style="color:#92400e;">Riwayat Oper Kredit</span>
                    <span class="stat-value" style="font-size:13px; color:#92400e;">{{ $sale->status_before_cancel }}</span>
                </div>
                @endif
            </div>

            <div class="card" style="gap:10px;">
                <h3 class="panel-title" style="padding:0 0 6px 0;">Ringkasan Finansial</h3>
                <div class="grid-2" style="column-gap:18px;">
                    <div class="field">
                        <span class="hint">Harga Jual</span>
                        <span class="stat-value" style="font-size:16px;">Rp {{ number_format($penjualan['harga_jual'], 0, ',', '.') }}</span>
                    </div>
                    <div class="field">
                        <span class="hint">Total Terbayar</span>
                        <span class="stat-value" style="font-size:16px; color:#166534;">Rp {{ number_format($penjualan['total_terbayar'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="field" style="margin-top:8px;">
                    <span class="hint">Sisa Piutang</span>
                    <span class="stat-value" style="font-size:18px; color:#b4232a;">Rp {{ number_format($penjualan['sisa_piutang'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="card" style="gap:6px;">
                <h3 class="panel-title" style="padding:0 0 6px 0;">Jadwal Angsuran</h3>
                <div style="max-height: 320px; overflow:auto;">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th style="width:46px;">#</th>
                                <th>Jatuh Tempo</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sale->payments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ optional($payment->due_date)->format('d M Y') }}</td>
                                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="status-chip {{ $payment->status === 'paid' ? 'success' : 'info' }}">
                                            {{ $payment->status }}
                                        </span>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        @if ($payment->status === 'unpaid')
                                            <form action="{{ route('payments.update', $payment) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn" style="padding:6px 10px; color:#b4232a; border:none; background:none; font-weight:700; text-decoration:none; cursor:pointer;">Bayar Lunas</button>
                                            </form>
                                        @else
                                            <span style="font-size:12px; color:#64748b;">{{ optional($payment->paid_at)->format('d M Y') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; color:#64748b;">Tidak ada jadwal angsuran (cash keras)</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px; border-top:1px solid #e5e7eb; padding-top:12px;">
                    <h4 class="panel-title" style="padding:0 0 8px 0;">Pembayaran Fleksibel</h4>
                    <form action="{{ route('payments.store') }}" method="POST" style="display:flex; flex-direction:column; gap:8px;">
                        @csrf
                        <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                        <div class="grid-2" style="column-gap:12px;">
                            <div class="field">
                                <label class="hint">Nominal (Rp)</label>
                                <input class="input" type="number" name="amount" min="1" placeholder="Masukkan nominal">
                            </div>
                            <div class="field">
                                <label class="hint">Tanggal</label>
                                <input class="input" type="date" name="date" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="field">
                            <label class="hint">Catatan</label>
                            <input class="input" type="text" name="note" placeholder="Pembayaran Fleksibel">
                        </div>
                        <button type="submit" class="btn primary" style="align-self:flex-end;">Simpan Pembayaran</button>
                    </form>
                </div>
            </div>

            <div class="card" style="gap:6px;">
                <h3 class="panel-title" style="padding:0 0 6px 0;">Riwayat Pembayaran</h3>
                <div style="overflow-x:auto;">
                    <table class="table-clean" id="paymentHistoryTable">
                        <thead>
                            <tr>
                                <th style="width:140px; cursor:pointer; user-select:none;" data-sort="date" onclick="sortPaymentHistory('date')">
                                    Tanggal <span class="sort-icon" id="sort-icon-date">↕</span>
                                </th>
                                <th style="cursor:pointer; user-select:none;" data-sort="note" onclick="sortPaymentHistory('note')">
                                    Keterangan <span class="sort-icon" id="sort-icon-note">↕</span>
                                </th>
                                <th style="text-align:right; cursor:pointer; user-select:none;" data-sort="amount" onclick="sortPaymentHistory('amount')">
                                    Jumlah <span class="sort-icon" id="sort-icon-amount">↕</span>
                                </th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryBody">
                            @forelse ($sale->payments()->where('status', 'paid')->orderBy('paid_at')->get() as $pay)
                                <tr data-date="{{ optional($pay->paid_at)->format('Y-m-d') ?? optional($pay->due_date)->format('Y-m-d') }}" data-note="{{ strtolower($pay->note ?? 'pembayaran') }}" data-amount="{{ $pay->amount }}">
                                    <td>{{ optional($pay->paid_at)->format('d M Y') ?? optional($pay->due_date)->format('d M Y') }}</td>
                                    <td>{{ $pay->note ?? 'Pembayaran' }}</td>
                                    <td style="text-align:right;">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                                    <td style="text-align:right;">
                                        <button type="button" class="btn" data-action="print-payment" data-amount="{{ $pay->amount }}" data-note="{{ $pay->note ?? 'Pembayaran' }}" data-date="{{ optional($pay->paid_at)->format('d M Y') ?? optional($pay->due_date)->format('d M Y') }}" style="padding:6px 10px; color:#b4232a; border-color: #f3f4f6; box-shadow:none; cursor:pointer;">Cetak</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center; color:#64748b;">Belum ada pembayaran</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="receiptModal" class="modal-backdrop">
            <div class="modal-card" style="width:min(1020px,100%); gap:0;">
            <div class="modal-body" style="max-height: 76vh; display:flex; justify-content:center;">
                <div id="receiptContent" style="background:#fff; border:1px solid #e5e7eb; padding:26px; border-radius:12px; color:#111827; width:100%; max-width:660px; margin:0 auto;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:18px;">
                        <div style="display:flex; gap:12px; align-items:flex-start;">
                            <div class="upload-preview" style="width:64px; height:64px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; padding:6px;">
                                @php
                                    $receiptLogoFilename = basename($companyLogo);
                                    $receiptLogoUrl = url('/native-img/logos/' . $receiptLogoFilename) . '?v=' . time();
                                @endphp
                                <img class="company-logo-img"
     src="{{ $receiptLogoUrl }}"
     alt="Logo Perusahaan"
     style="max-width:100%; max-height:100%; object-fit:contain; display:block;"
     onerror="this.onerror=null;this.src='{{ asset('/logo-app.png') }}';">
                            </div>
                            <div>
                                <div style="font-size:20px; font-weight:800;">{{ optional($companyProfile)->name ?? config('company.name') }}</div>
                                <div style="font-size:12px; color:#475569;">{{ optional($companyProfile)->address ?? config('company.address') }}</div>
                                <div style="font-size:12px; color:#475569;">Telp: {{ optional($companyProfile)->phone ?? config('company.phone') }} | Email: {{ optional($companyProfile)->email ?? config('company.email') }}</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:24px; font-weight:800;">KUITANSI</div>
                            <div style="font-size:12px; color:#475569;" id="receiptNumber">No: {{ $receiptNumber }}</div>
                        </div>
                    </div>
                    <hr>
                    <div style="display:grid; grid-template-columns: 170px 1fr; row-gap:10px; column-gap:12px; margin-top:10px;">
                        <div>Telah Diterima Dari</div>
                        <div>: <strong id="receiptBuyer">{{ $penjualan['pembeli'] }}</strong></div>
                        <div>Uang Sejumlah</div>
                        <div>: <em id="receiptTerbilang" style="background:#f3f4f6; padding:2px 4px; border-radius:4px;">-</em></div>
                        <div>Untuk Pembayaran</div>
                        <div>: <span id="receiptNote">Pembayaran</span></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin:28px 0 18px;">
                        <div id="receiptAmountBox" style="border:2px solid #111827; padding:14px 18px; font-weight:800; font-size:20px;">Rp 0</div>
        <div style="text-align:right; line-height:1.5; font-size:13px;">
            <div id="receiptDate">{{ now()->format('d M Y') }}</div>
            <div style="margin-top:32px; border-top:1px solid #111827; padding-top:6px; font-weight:700;">{{ optional($companyProfile)->signer_name ?? 'Admin Keuangan' }}</div>
        </div>
    </div>
                    <hr>
                    <div style="text-align:center; font-size:13px; color:#475569; margin-top:12px;">{{ optional($companyProfile)->footer_note ?? 'Terima kasih atas pembayaran Anda.' }}</div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="closeReceiptModal" class="btn light">Tutup</button>
                <button id="printReceipt" class="btn primary">Cetak Kuitansi</button>
            </div>
        </div>
    </div>
    @include('penjualan.cancel_modal')
@endsection

@push('scripts')
    <script>
        const cancelBtn = document.getElementById('cancelSaleBtn');
        const cancelModal = document.getElementById('cancelModal');
        const closeCancel = document.getElementById('closeCancelModal');
        const cancelForm = document.getElementById('cancelForm');
        const refundInput = document.getElementById('refundInput');
        const operKreditInput = document.getElementById('operKreditInput');
        const newBuyerSelect = document.getElementById('newBuyerSelect');
        const typeRadios = document.getElementsByName('type');

        if(cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                cancelModal.classList.add('active');
            });
            closeCancel.addEventListener('click', () => {
                cancelModal.classList.remove('active');
            });
            
            typeRadios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    // Hide all extra fields first
                    refundInput.classList.add('hidden');
                    operKreditInput.classList.add('hidden');
                    newBuyerSelect.removeAttribute('required');
                    
                    // Show relevant field based on selection
                    if(e.target.value === 'refund') {
                        refundInput.classList.remove('hidden');
                    } else if(e.target.value === 'oper_kredit') {
                        operKreditInput.classList.remove('hidden');
                        newBuyerSelect.setAttribute('required', 'required');
                    }
                });
            });

            // Currency formatter
            const currencyInputs = document.querySelectorAll('.currency-input');
            currencyInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    let val = e.target.value.replace(/\D/g, '');
                    if (val) {
                        e.target.value = new Intl.NumberFormat('id-ID').format(val);
                    }
                });
            });
        }

        // Notes inline edit
        const editNotesBtn = document.getElementById('editNotesBtn');
        const notesDisplay = document.getElementById('notesDisplay');
        const notesEditForm = document.getElementById('notesEditForm');
        const cancelNotesEdit = document.getElementById('cancelNotesEdit');

        if (editNotesBtn) {
            editNotesBtn.addEventListener('click', () => {
                notesDisplay.style.display = 'none';
                notesEditForm.style.display = 'block';
                editNotesBtn.style.display = 'none';
            });

            cancelNotesEdit.addEventListener('click', () => {
                notesDisplay.style.display = 'block';
                notesEditForm.style.display = 'none';
                editNotesBtn.style.display = 'inline-flex';
            });
        }
    </script>
    <script>
        // Print-only styling: tampilkan hanya konten kuitansi saat print
        const printStyle = document.createElement('style');
        printStyle.innerHTML = `
            @media print {
                html, body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #fff !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                @page {
                    size: A4 portrait;
                    margin: 12mm;
                }
                
                /* Hide everything except the receipt */
                .page-heading,
                .panel-grid,
                .sidebar,
                .navbar,
                .modal-footer,
                #cancelModal,
                nav,
                header,
                footer,
                .filter-row {
                    display: none !important;
                }
                
                /* Show and style the receipt modal */
                #receiptModal {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    background: #fff !important;
                    display: block !important;
                    overflow: visible !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    z-index: 999999 !important;
                }
                
                #receiptModal.active {
                    display: block !important;
                }
                
                #receiptModal .modal-backdrop {
                    background: #fff !important;
                }
                
                #receiptModal .modal-card {
                    box-shadow: none !important;
                    border: none !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    position: static !important;
                    transform: none !important;
                }
                
                #receiptModal .modal-body {
                    max-height: none !important;
                    overflow: visible !important;
                }
                
                #receiptContent {
                    box-shadow: none !important;
                    border: 1px solid #e5e7eb !important;
                    padding: 26px !important;
                    margin: 0 auto !important;
                    width: 660px !important;
                    max-width: 660px !important;
                    background: #fff !important;
                    color: #111827 !important;
                }
                
                #receiptContent * {
                    color: inherit !important;
                    background: transparent !important;
                }
                
                #receiptContent .upload-preview {
                    background: #f3f4f6 !important;
                }
                
                #receiptContent #receiptTerbilang {
                    background: #f3f4f6 !important;
                }
                
                #receiptContent #receiptAmountBox {
                    border: 2px solid #111827 !important;
                }
            }
        `;
        document.head.appendChild(printStyle);

        const receiptModal = document.getElementById('receiptModal');
        const closeReceiptModal = document.getElementById('closeReceiptModal');
        const printReceiptBtn = document.getElementById('printReceipt');
        const sale = @json($sale->load('buyer', 'payments'));

        function formatNumber(n) {
            return (Number(n) || 0).toLocaleString('id-ID');
        }

        function updateReceipt({ amount, note, date }) {
            document.getElementById('receiptAmountBox').innerText = `Rp ${formatNumber(amount)}`;
            document.getElementById('receiptNote').innerText = note || 'Pembayaran';
            document.getElementById('receiptDate').innerText = date || new Date().toLocaleDateString('id-ID');
            document.getElementById('receiptTerbilang').innerText = terbilang(amount);
        }

        function terbilang(angka) {
            const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            return formatter.format(angka).replace('Rp', '').trim() + ' rupiah';
        }

        function bindPrintButtons() {
            document.querySelectorAll('[data-action="print-payment"]').forEach((btn) => {
                if (btn.dataset.bound === '1') return;
                btn.dataset.bound = '1';
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const amount = btn.dataset.amount;
                    const note = btn.dataset.note;
                    const date = btn.dataset.date;
                    updateReceipt({ amount, note, date });
                    receiptModal?.classList.add('active');
                });
            });
        }
        const rebind = () => bindPrintButtons();
        if (document.readyState !== 'loading') {
            rebind();
        } else {
            document.addEventListener('DOMContentLoaded', rebind, { once: true });
        }
        document.addEventListener('turbo:load', rebind);

        closeReceiptModal?.addEventListener('click', () => receiptModal?.classList.remove('active'));
        receiptModal?.addEventListener('click', (e) => {
            if (e.target === receiptModal) receiptModal.classList.remove('active');
        });

        printReceiptBtn?.addEventListener('click', () => {
            window.print();
        });

        // Kirim Tagihan via WhatsApp
        const reminderBtn = document.getElementById('sendReminder');
        if (reminderBtn) {
            reminderBtn.addEventListener('click', () => {
                const firstUnpaid = sale.payments?.find((p) => p.status === 'unpaid');
                const buyerPhone = sale.buyer?.phone;

                if (!firstUnpaid || !buyerPhone) {
                    alert('Tidak ada angsuran yang belum dibayar atau nomor telepon buyer tidak tersedia.');
                    return;
                }

                const dueDate = new Date(firstUnpaid.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                const amount = formatNumber(firstUnpaid.amount);
                const companyName = "{{ optional($companyProfile)->name ?? config('company.name') }}";
                const kavlingInfo = `{{ $penjualan['kavling'] }}`;
                const buyerName = sale.buyer.name;

                const text = `Halo Bapak/Ibu ${buyerName}, kami dari ${companyName}.
Mengingatkan pembayaran angsuran untuk kavling ${kavlingInfo} akan jatuh tempo pada ${dueDate} dengan sisa tagihan sebesar Rp ${amount}.

Mohon untuk segera melakukan pembayaran untuk kelancaran proses administrasi.
Terima kasih.`;
                const encoded = encodeURIComponent(text);
                const normalized = buyerPhone.startsWith('62') ? buyerPhone : buyerPhone.replace(/^0/, '62');
                const url = `https://api.whatsapp.com/send?phone=${normalized}&text=${encoded}`;
                window.open(url, '_blank');
            });
        }

        // Sort Payment History Table
        let currentSort = { column: null, direction: 'asc' };

        function sortPaymentHistory(column) {
            const tbody = document.getElementById('paymentHistoryBody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-date]'));
            if (rows.length === 0) return;

            // Toggle direction if same column
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            // Sort rows
            rows.sort((a, b) => {
                let valueA, valueB;

                if (column === 'date') {
                    valueA = a.dataset.date || '';
                    valueB = b.dataset.date || '';
                } else if (column === 'note') {
                    valueA = a.dataset.note || '';
                    valueB = b.dataset.note || '';
                } else if (column === 'amount') {
                    valueA = parseFloat(a.dataset.amount) || 0;
                    valueB = parseFloat(b.dataset.amount) || 0;
                }

                let comparison = 0;
                if (column === 'amount') {
                    comparison = valueA - valueB;
                } else {
                    comparison = valueA.localeCompare(valueB, 'id-ID');
                }

                return currentSort.direction === 'asc' ? comparison : -comparison;
            });

            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));

            // Update sort icons
            document.querySelectorAll('.sort-icon').forEach(icon => {
                icon.textContent = '↕';
                icon.style.opacity = '0.4';
            });

            const activeIcon = document.getElementById(`sort-icon-${column}`);
            if (activeIcon) {
                activeIcon.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
                activeIcon.style.opacity = '1';
            }

            // Rebind print buttons after sorting
            bindPrintButtons();
        }

        // Initialize sort icons style
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.style.opacity = '0.4';
            icon.style.marginLeft = '4px';
        });
    </script>
@endpush
