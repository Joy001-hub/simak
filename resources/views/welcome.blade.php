<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kavling Management Pro</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark logo">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#9c0f2f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="#9c0f2f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="#9c0f2f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">Alma Jaya</span>
                <span class="brand-sub">Kavling Management</span>
            </div>
        </div>

        <nav class="nav">
            <a href="#" class="nav-item active">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="label">Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span class="label">Data Kavling</span>
            </a>
            <a href="#" class="nav-item">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span class="label">Pelanggan</span>
            </a>
            <a href="#" class="nav-item">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span class="label">Laporan</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-card">
                <span class="hint">Login sebagai:</span>
                <div class="card-label">Admin Utama</div>
            </div>
        </div>
    </aside>

    <main class="content-area">
        <div class="page">

            <div class="page-heading">
                <div>
                    <h1 class="heading-title">Overview Penjualan</h1>
                    <p class="heading-sub">Pantau performa penjualan kavling Alma Areca Nut.</p>
                </div>
                <div class="filter-row">
                    <button class="btn light">Export Data</button>
                    <button class="btn primary">+ Transaksi Baru</button>
                </div>
            </div>

            <div class="summary-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-label">Total Omzet</span>
                        <span class="pill primary">+12%</span>
                    </div>
                    <div class="stat">
                        <span class="stat-unit">Rp</span>
                        <span class="stat-value">850.000.000</span>
                    </div>
                    <p class="hint">Update hari ini</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-label">Unit Terjual</span>
                        <span class="pill soft">Bulan Ini</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">12</span>
                        <span class="stat-unit">Kavling</span>
                    </div>
                    <p class="hint">Sisa 45 unit tersedia</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-label">Pelanggan Baru</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">8</span>
                        <span class="stat-unit">Orang</span>
                    </div>
                    <p class="hint">Menunggu verifikasi: 2</p>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Transaksi Terakhir</h2>
                    <a href="#" class="btn light">Lihat Semua</a>
                </div>
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>Kavling</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Blok A-12</td>
                            <td>Bpk. Budi Santoso</td>
                            <td><span class="status-chip success">Lunas</span></td>
                            <td>Rp 45.000.000</td>
                        </tr>
                        <tr>
                            <td>Blok C-05</td>
                            <td>Ibu Siti Aminah</td>
                            <td><span class="status-chip info">Cicilan</span></td>
                            <td>Rp 40.000.000</td>
                        </tr>
                        <tr>
                            <td>Blok B-01</td>
                            <td>PT. Maju Mundur</td>
                            <td><span class="status-chip danger">Pending</span></td>
                            <td>Rp 120.000.000</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>
