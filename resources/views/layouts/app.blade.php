<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} | Dashboard</title>
    <meta name="turbo-cache-control" content="no-cache">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%239c0f2f'/%3E%3Cpath d='M18 20h28v8H34v16H18V20Zm14 8h14v16H32V28Z' fill='%23fff'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .content-area {
            flex: 1;
            padding: 20px;
        }

        .content-area .page {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .table-clean th,
        .table-clean td {
            white-space: nowrap;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark logo" aria-label="SIMAK Logo" style="width:190px; height:auto;">
                    <img class="app-logo-img" src="{{ asset('/logo-app.png') }}" alt="SIMAK Logo"
                        onerror="this.onerror=null;this.src='{{ asset('/logo-simak.svg') }}';"
                        style="width:190px; height:auto;">
                </div>
                <div class="brand-text">
                    <span class="brand-sub">Sistem Informasi Manajemen Kavling</span>
                </div>
            </div>

            <nav class="nav">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path
                            d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-4.5a.5.5 0 0 1-.5-.5v-4a1 1 0 0 0-1-1h-3a1 1 0 0 0-1 1v4a.5.5 0 0 1-.5.5H5a1 1 0 0 1-1-1v-8.5Z" />
                    </svg>
                    <span class="label">Dashboard</span>
                </a>
                <a class="nav-item {{ request()->routeIs('penjualan.*') ? 'active' : '' }}"
                    href="{{ route('penjualan.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="6" width="18" height="12" rx="2" ry="2" />
                        <path d="M7 10h10M7 14h6" />
                    </svg>
                    <span class="label">Penjualan</span>
                </a>
                <a class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}"
                    href="{{ route('projects.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5 5h14v6H5zM5 13h8v6H5zM15 13h4v6h-4z" />
                    </svg>
                    <span class="label">Projects</span>
                </a>
                <a class="nav-item {{ request()->routeIs('kavling.*') ? 'active' : '' }}"
                    href="{{ route('kavling.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="4" width="7" height="7" rx="1.5" />
                        <rect x="4" y="13" width="7" height="7" rx="1.5" />
                        <rect x="13" y="4" width="7" height="7" rx="1.5" />
                        <path d="M16 15h4v5h-4z" />
                    </svg>
                    <span class="label">Kavling</span>
                </a>
                <a class="nav-item {{ request()->routeIs('buyers.*') ? 'active' : '' }}"
                    href="{{ route('buyers.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-8 8a8 8 0 0 1 16 0" />
                    </svg>
                    <span class="label">Buyer</span>
                </a>
                <a class="nav-item {{ request()->routeIs('marketing.*') ? 'active' : '' }}"
                    href="{{ route('marketing.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="8" cy="7" r="3" />
                        <circle cx="17" cy="7" r="3" />
                        <path d="M3 20a5 5 0 0 1 10 0M11 20h10a4 4 0 0 0-4-4h-2" />
                    </svg>
                    <span class="label">Tim Marketing</span>
                </a>
                <a class="nav-item {{ request()->routeIs('data.*') ? 'active' : '' }}" href="{{ route('data.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="4" width="18" height="6" rx="1.5" />
                        <rect x="3" y="14" width="18" height="6" rx="1.5" />
                        <path d="M7 7h.01M12 7h.01M17 7h.01M7 17h.01M12 17h.01M17 17h.01" />
                    </svg>
                    <span class="label">Manajemen Data</span>
                </a>
                <a class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                    href="{{ route('profile.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="4" width="16" height="16" rx="2" />
                        <path d="M8 11h8M8 15h6" />
                    </svg>
                    <span class="label">Profil Perusahaan</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-card" style="display:flex; align-items:center; gap:10px; min-height:56px;">
                    <div class="upload-preview"
                        style="width:48px; height:48px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                        @php
                            // 1. Ambil nama file-nya saja (misal: "gambar.png") dari URL yang error tadi
                            $filename = basename($companyLogo);

                            // 2. Arahkan ke route baru di web.php (/native-img/logos/...)
                            // 3. Tambahkan time() agar kalau ganti logo, langsung berubah (anti-cache)
                            $nativeUrl = url('/native-img/logos/' . $filename) . '?v=' . time();
                        @endphp

                        <img class="company-logo-img" src="{{ $nativeUrl }}" alt="Logo perusahaan"
                            onerror="this.onerror=null;this.src='{{ asset('/logo-app.png') }}';">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px; overflow:hidden;">
                        <div class="brand-sub"
                            style="color:#0f172a; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">
                            {{ optional($companyProfile ?? null)->name ?? config('company.name') }}
                        </div>
                        <div class="hint"
                            style="color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">
                            {{ optional($companyProfile ?? null)->email ?? config('company.email') }}
                        </div>
                    </div>
                </div>
                <a id="sidebarLogoutBtn" href="{{ route('logout') }}"
                    style="margin-top:12px; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 12px; border-radius:12px; border:1px solid #fecdd3; background:#fff1f2; color:#b91c1c; font-weight:700; cursor:pointer; transition:background 0.2s ease; text-decoration:none;"
                    onmouseover="this.style.background='#ffe4e6'" onmouseout="this.style.background='#fff1f2'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <div class="content-area">
            <main class="page">
                @if($errors->any())
                    <div class="card"
                        style="border-left:4px solid #ef4444; padding:16px; background:#fef2f2; color:#991b1b; max-width: 1100px; margin: 0 auto 24px auto; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <div style="font-weight: 700; margin-bottom: 8px;">Terdapat kesalahan pada input:</div>
                        <ul style="margin:0; padding-left:20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <div id="toast" class="floating-alert" ari&times;live="polite" aria-label="Notifikasi"></div>
    <div id="bellButton" class="bell-floating">
        <div class="bell-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round"
                stroke-linejoin="round" ari&times;hidden="true">
                <path
                    d="M24 10c-6.6 0-12 5.4-12 12v6.5c0 1-.4 2-1.1 2.7l-1.3 1.3c-.2.2 0 .5.3.5h28c.3 0 .4-.3.3-.5l-1.3-1.3A3.8 3.8 0 0 1 36 28.5V22c0-6.6-5.4-12-12-12Z" />
                <path d="M20 34a4 4 0 0 0 8 0" />
            </svg>
        </div>
        @if(($overdueCount ?? 0) > 0)
            <span class="bell-badge">{{ $overdueCount }}</span>
        @endif
    </div>

    <div id="bellPanel" class="bell-panel" aria-label="Notifikasi penagihan">
        <div class="bell-panel-header">
            <div>
                <div class="bell-title">Daftar Penagihan Mendesak</div>
                <div class="bell-sub">Total {{ $overdueCount ?? 0 }} angsuran dari {{ count($overduePayments ?? []) }} pelanggan telah jatuh tempo.
                </div>
            </div>
            <button id="bellClose" type="button" aria-label="Tutup notifikasi"
                style="background:transparent; border:none; color:#fff; font-size:16px; cursor:pointer; line-height:1; font-weight:600;">×</button>
        </div>
        <div class="bell-panel-body">
            @forelse($overduePayments ?? [] as $payment)
                @php
                    $sale = $payment->sale;
                    $buyer = optional($sale)->buyer;
                    $totalOverdue = $payment->total_overdue_amount ?? $payment->amount;
                    $overduePaymentCount = $payment->overdue_payment_count ?? 1;
                    $kavlingList = $payment->kavling_list ?? [];
                    $lateDaysRaw = $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->diffInDays(\Carbon\Carbon::now()) : 0;
                    $lateDays = max(0, (int) $lateDaysRaw);
                    $totalMonths = intdiv($lateDays, 30);
                    $remainingAfterMonths = $lateDays % 30;
                    $years = intdiv($totalMonths, 12);
                    $months = $totalMonths % 12;
                    $weeks = intdiv($remainingAfterMonths, 7);
                    $days = $remainingAfterMonths % 7;
                    $parts = [];
                    if ($years > 0)
                        $parts[] = $years . ' tahun';
                    if ($months > 0)
                        $parts[] = $months . ' bulan';
                    if ($weeks > 0)
                        $parts[] = $weeks . ' minggu';
                    if ($days > 0 || empty($parts))
                        $parts[] = $days . ' hari';
                    $lateLabel = implode(', ', $parts);
                @endphp
                <div class="bell-card">
                    <div class="bell-card-content">
                        <strong class="bell-card-name">{{ $buyer->name ?? 'Tidak diketahui' }}</strong>
                        <div class="bell-card-info">
                            <div class="bell-info-row">
                                <span class="bell-info-label">{{ $overduePaymentCount }} angsuran tertunggak</span>
                                @if(count($kavlingList) > 1)
                                    <span class="bell-info-kavling" style="font-size:11px; color:#64748b;">({{ count($kavlingList) }} kavling)</span>
                                @endif
                                <br />
                                <span class="bell-info-value">Total: Rp {{ number_format($totalOverdue, 0, ',', '.') }}</span>
                            </div>
                            <span class="bell-info-late">Terlambat {{ $lateLabel }}</span>
                        </div>
                    </div>
                    @if(count($kavlingList) === 1)
                        <a class="bell-btn" href="{{ route('penjualan.show', $kavlingList[0]['sale_id']) }}">Lihat Detail</a>
                    @else
                        <button type="button" class="bell-btn bell-btn-popup" data-kavling='@json($kavlingList)' data-buyer="{{ $buyer->name ?? 'Pembeli' }}">Lihat Detail</button>
                    @endif
                </div>
            @empty
                <p class="bell-empty">Tidak ada penagihan jatuh tempo.</p>
            @endforelse
        </div>
    </div>


    <style>
        .bell-floating {
            position: fixed;
            bottom: 18px;
            right: 18px;
            z-index: 50;
        }

        .bell-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(145deg, #8e0f2a, #a21535);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(156, 15, 47, 0.24);
            cursor: pointer;
        }

        .bell-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #e11d48;
            color: #fff;
            border-radius: 999px;
            min-width: 20px;
            padding: 3px 6px;
            font-size: 12px;
            font-weight: 800;
            border: 2px solid #fff;
            text-align: center;
            line-height: 1;
        }

        .bell-panel {
            position: fixed;
            top: 0;
            right: -420px;
            width: 360px;
            height: 100vh;
            background: #fff;
            box-shadow: -8px 0 24px rgba(0, 0, 0, 0.12);
            z-index: 49;
            display: flex;
            flex-direction: column;
            transition: right 0.25s ease;
        }

        .bell-panel.active {
            right: 0;
        }

        .bell-panel-header {
            background: #b91c3b;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .bell-panel-body {
            padding: 16px;
            overflow: auto;
            flex: 1;
            background: #f9fafb;
        }

        .bell-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .bell-card-content {
            flex: 1;
            min-width: 0;
        }

        .bell-card-name {
            display: block;
            color: #1f2937;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .bell-card-info {
            font-size: 12px;
            line-height: 1.4;
        }

        .bell-info-row {
            color: #666;
            margin-bottom: 4px;
        }

        .bell-info-label {
            color: #666;
        }

        .bell-info-value {
            color: #b91c3b;
            font-weight: 700;
        }

        .bell-info-late {
            color: #2563eb;
            display: block;
            margin-top: 6px;
        }

        .bell-btn {
            flex-shrink: 0;
            padding: 8px 20px;
            background: #b91c3b;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            display: inline-block;
        }

        .bell-btn:hover {
            background: #9a1630;
        }

        .bell-empty {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
            text-align: center;
            padding: 20px;
        }

        .bell-title {
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            margin: 0;
        }

        .bell-sub {
            color: #fce7eb;
            font-size: 12px;
            margin: 4px 0 0;
        }

        /* Kavling Popup Modal */
        .kavling-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .kavling-popup-overlay.active {
            display: flex;
        }
        .kavling-popup {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 360px;
            width: 100%;
            overflow: hidden;
            animation: popupIn 0.2s ease;
        }
        @keyframes popupIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .kavling-popup-header {
            background: linear-gradient(145deg, #b91c3b, #9a1630);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kavling-popup-title {
            font-weight: 700;
            font-size: 15px;
        }
        .kavling-popup-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            opacity: 0.8;
        }
        .kavling-popup-close:hover {
            opacity: 1;
        }
        .kavling-popup-body {
            padding: 16px 20px;
        }
        .kavling-popup-subtitle {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .kavling-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .kavling-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.15s ease;
        }
        .kavling-item:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }
        .kavling-item-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .kavling-item-name {
            font-weight: 600;
            font-size: 14px;
        }
        .kavling-item-detail {
            font-size: 11px;
            color: #6b7280;
        }
        .kavling-item-arrow {
            color: #9ca3af;
            font-size: 18px;
        }
        .kavling-item:hover .kavling-item-arrow {
            color: #b91c3b;
        }
    </style>
    <script>
        (function () {
            const toast = document.getElementById('toast');
            const message = @json(session('success') ?? session('error') ?? '');
            if (toast && message) {
                toast.textContent = message;
                toast.classList.add('active');
                setTimeout(() => toast.classList.remove('active'), 4000);
            }

            const bellButton = document.getElementById('bellButton');
            const bellPanel = document.getElementById('bellPanel');
            const bellClose = document.getElementById('bellClose');
            const toggleBell = () => {
                if (!bellPanel || ({{ $overdueCount ?? 0 }} === 0)) return;
                bellPanel.classList.toggle('active');
            };
            bellButton?.addEventListener('click', toggleBell);
            bellClose?.addEventListener('click', () => bellPanel?.classList.remove('active'));
            bellPanel?.addEventListener('click', (e) => { if (e.target === bellPanel) bellPanel.classList.remove('active'); });
        })();

        // Logout button on sidebar - redirects to login page
        (() => {
            const btn = document.getElementById('sidebarLogoutBtn');
            if (!btn) return;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const confirmLogout = confirm('Keluar dari SIMAK? Anda akan diarahkan ke halaman login.');
                if (!confirmLogout) return;

                // Redirect to logout route
                window.location.href = btn.getAttribute('href') || '/logout';
            });
        })();
    </script>

    {{-- Include Maintenance Modal Component --}}
    @include('components.maintenance-modal')

    {{-- Kavling Selection Popup --}}
    <div id="kavlingPopupOverlay" class="kavling-popup-overlay">
        <div class="kavling-popup">
            <div class="kavling-popup-header">
                <span class="kavling-popup-title" id="kavlingPopupTitle">Pilih Kavling</span>
                <button type="button" class="kavling-popup-close" id="kavlingPopupClose">&times;</button>
            </div>
            <div class="kavling-popup-body">
                <div class="kavling-popup-subtitle">Pembeli ini memiliki tunggakan di beberapa kavling:</div>
                <div class="kavling-list" id="kavlingList"></div>
            </div>
        </div>
    </div>

    <script>
        // Kavling popup handler
        (function() {
            const overlay = document.getElementById('kavlingPopupOverlay');
            const popupTitle = document.getElementById('kavlingPopupTitle');
            const kavlingList = document.getElementById('kavlingList');
            const closeBtn = document.getElementById('kavlingPopupClose');

            function formatRupiah(num) {
                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function showKavlingPopup(buyerName, kavlings) {
                popupTitle.textContent = buyerName;
                kavlingList.innerHTML = kavlings.map(k => `
                    <a href="/penjualan/${k.sale_id}" class="kavling-item">
                        <div class="kavling-item-info">
                            <span class="kavling-item-name">${k.kavling || 'Kavling'}</span>
                            <span class="kavling-item-detail">${k.count} angsuran · ${formatRupiah(k.amount)}</span>
                        </div>
                        <span class="kavling-item-arrow">→</span>
                    </a>
                `).join('');
                overlay.classList.add('active');
            }

            function closePopup() {
                overlay.classList.remove('active');
            }

            // Event listeners
            closeBtn?.addEventListener('click', closePopup);
            overlay?.addEventListener('click', (e) => {
                if (e.target === overlay) closePopup();
            });

            // Handle popup button clicks
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.bell-btn-popup');
                if (btn) {
                    e.preventDefault();
                    const kavlings = JSON.parse(btn.dataset.kavling || '[]');
                    const buyerName = btn.dataset.buyer || 'Pembeli';
                    showKavlingPopup(buyerName, kavlings);
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('active')) {
                    closePopup();
                }
            });
        })();
    </script>

    {{-- Check untuk license validation failure --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cek jika ada cache flag bahwa validasi gagal
            const licenseValidationFailed = {!! json_encode(\Illuminate\Support\Facades\Cache::get('license_validation_failed', false)) !!};

            if (licenseValidationFailed) {
                // Tampilkan modal maintenance
                window.showMaintenanceModal?.();
            }
        });
    </script>

    @stack('scripts')
</body>

</html>