<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login_admin.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login_admin.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../Landing Page/styles.css" />
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: radial-gradient(circle at top, #73512C 0, #F9F4E1 55%);
            color: #543310;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
        }
        .admin-dashboard-shell {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: 100vh;
        }
        .admin-sidebar {
            background: linear-gradient(180deg, #543310, #73512C 50%, #B08F70 100%);
            border-right: 1px solid rgba(176, 143, 112, 0.65);
            padding: 1.5rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .admin-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .admin-brand-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.9rem;
            background: radial-gradient(circle at 30% 0, #F9F4E1, #D7B290 45%, #B08F70 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #543310;
            font-weight: 800;
            font-size: 0.85rem;
            box-shadow: 0 0 0 1px rgba(249, 244, 225, 0.9), 0 10px 25px rgba(84, 51, 16, 0.5);
        }
        .admin-brand-text {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }
        .admin-brand-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .admin-brand-subtitle {
            font-size: 0.7rem;
            color: #D7B290;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }
        .admin-nav-section-label {
            font-size: 0.75rem;
            color: #D7B290;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            margin-bottom: 0.25rem;
        }
        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .admin-nav-item {
            border-radius: 0.65rem;
            padding: 0.55rem 0.7rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            color: #F9F4E1;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease, transform 0.08s ease;
        }
        .admin-nav-item.active {
            background: radial-gradient(circle at 0 0, rgba(249, 244, 225, 0.25), rgba(176, 143, 112, 0.9));
            border-color: rgba(249, 244, 225, 0.9);
            color: #543310;
        }
        .admin-nav-item:hover {
            background: rgba(249, 244, 225, 0.16);
            border-color: rgba(176, 143, 112, 0.9);
            transform: translateY(-1px);
        }
        .admin-nav-item-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #B08F70;
            box-shadow: 0 0 0 3px rgba(249, 244, 225, 0.4);
        }
        .admin-nav-footer {
            margin-top: auto;
            padding-top: 1.25rem;
            border-top: 1px dashed rgba(249, 244, 225, 0.35);
        }
        .admin-logout-btn {
            width: 100%;
            border-radius: 999px;
            border: 1px solid rgba(176, 143, 112, 0.85);
            padding: 0.6rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.08em;
            background: radial-gradient(circle at 0 0, rgba(249, 244, 225, 0.85), rgba(176, 143, 112, 0.95));
            color: #543310;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            text-decoration: none;
            text-transform: uppercase;
            transition: background 0.12s ease, transform 0.08s ease, box-shadow 0.12s ease;
        }
        .admin-logout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 26px rgba(84, 51, 16, 0.35);
        }
        .admin-logout-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #F9F4E1;
        }
        .admin-main {
            padding: 1.5rem 2rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .admin-main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
        }
        .admin-page-title {
            font-size: 1.4rem;
            font-weight: 700;
        }
        .admin-page-subtitle {
            font-size: 0.85rem;
            color: #73512C;
            margin-top: 0.15rem;
        }
        .admin-status-pill {
            padding: 0.3rem 0.6rem;
            border-radius: 999px;
            border: 1px solid rgba(176, 143, 112, 0.8);
            background: rgba(249, 244, 225, 0.25);
            font-size: 0.7rem;
            color: #543310;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }
        .admin-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #F9F4E1;
            box-shadow: 0 0 0 3px rgba(176, 143, 112, 0.55);
        }
        .admin-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
            gap: 1.25rem;
        }
        .admin-card {
            border-radius: 1rem;
            padding: 1.1rem 1.1rem 1.15rem;
            background: radial-gradient(circle at 0 0, rgba(176, 143, 112, 0.15), rgba(249, 244, 225, 0.98));
            border: 1px solid rgba(176, 143, 112, 0.75);
            box-shadow: 0 18px 40px rgba(84, 51, 16, 0.25);
        }
        .admin-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .admin-card-title {
            font-size: 0.95rem;
            font-weight: 600;
        }
        .admin-card-subtitle {
            font-size: 0.75rem;
            color: #73512C;
            margin-top: 0.2rem;
        }
        .admin-badge {
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            border: 1px solid rgba(176, 143, 112, 0.8);
            font-size: 0.7rem;
            color: #543310;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }
        .admin-metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.7rem;
            margin-top: 0.65rem;
        }
        .admin-metric-pill {
            border-radius: 0.9rem;
            padding: 0.6rem 0.65rem;
            background: rgba(249, 244, 225, 0.96);
            border: 1px solid rgba(176, 143, 112, 0.7);
        }
        .admin-metric-label {
            font-size: 0.7rem;
            color: #73512C;
            margin-bottom: 0.25rem;
        }
        .admin-metric-value {
            font-size: 1rem;
            font-weight: 700;
            color: #543310;
        }
        .admin-metric-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            font-size: 0.65rem;
        }
        .chip-green {
            background: rgba(176, 143, 112, 0.18);
            color: #543310;
        }
        .chip-blue {
            background: rgba(249, 244, 225, 0.9);
            color: #73512C;
        }
        .chip-amber {
            background: rgba(84, 51, 16, 0.08);
            color: #543310;
        }
        .admin-list {
            margin-top: 0.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            font-size: 0.78rem;
        }
        .admin-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.45rem 0.55rem;
            border-radius: 0.6rem;
            background: rgba(249, 244, 225, 0.95);
            border: 1px solid rgba(176, 143, 112, 0.8);
        }
        .admin-list-label {
            color: #543310;
        }
        .admin-list-meta {
            color: #73512C;
        }
        .admin-quick-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
            margin-top: 0.7rem;
        }
        .admin-action-btn {
            border-radius: 0.8rem;
            border: 1px dashed rgba(176, 143, 112, 0.9);
            padding: 0.55rem 0.6rem;
            background: rgba(249, 244, 225, 0.96);
            color: #543310;
            font-size: 0.8rem;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            cursor: pointer;
            transition: border-color 0.12s ease, background 0.12s ease, transform 0.08s ease;
        }
        .admin-action-btn span:nth-child(1) {
            font-weight: 500;
        }
        .admin-action-btn span:nth-child(2) {
            font-size: 0.7rem;
            color: #73512C;
        }
        .admin-action-btn:hover {
            border-style: solid;
            border-color: rgba(176, 143, 112, 0.95);
            background: radial-gradient(circle at 0 0, rgba(249, 244, 225, 0.9), rgba(215, 178, 144, 0.9));
            transform: translateY(-1px);
        }
        @media (max-width: 900px) {
            .admin-dashboard-shell {
                grid-template-columns: minmax(0, 1fr);
            }
            .admin-sidebar {
                display: none;
            }
            .admin-main {
                padding: 1.25rem 1.1rem 1.5rem;
            }
            .admin-main-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard-shell">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <div class="admin-brand-icon">AD</div>
                <div class="admin-brand-text">
                    <div class="admin-brand-title">Admin Panel</div>
                    <div class="admin-brand-subtitle">Control Center</div>
                </div>
            </div>

            <div>
                <div class="admin-nav-section-label">Menu</div>
                <nav class="admin-nav">
                    <div class="admin-nav-item active">
                        <span>Dashboard</span>
                        <span class="admin-nav-item-dot"></span>
                    </div>
                    <div class="admin-nav-item">
                        <span>Produk</span>
                    </div>
                    <div class="admin-nav-item">
                        <span>Pesanan</span>
                    </div>
                    <div class="admin-nav-item">
                        <span>Pengguna</span>
                    </div>
                </nav>
            </div>

            <div class="admin-nav-footer">
                <a href="?logout=1" class="admin-logout-btn">
                    <span class="admin-logout-dot"></span>
                    <span>Keluar</span>
                </a>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-main-header">
                <div>
                    <h1 class="admin-page-title">Dashboard</h1>
                    <p class="admin-page-subtitle">Ringkasan singkat aktivitas toko dan performa hari ini.</p>
                </div>
                <div class="admin-status-pill">
                    <span class="admin-status-dot"></span>
                    <span>Online</span>
                </div>
            </header>

            <section class="admin-main-grid">
                <article class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <div class="admin-card-title">Statistik Hari Ini</div>
                            <div class="admin-card-subtitle">Performa singkat penjualan dan kunjungan.</div>
                        </div>
                        <span class="admin-badge">Live</span>
                    </div>
                    <div class="admin-metrics-grid">
                        <div class="admin-metric-pill">
                            <div class="admin-metric-label">Penjualan</div>
                            <div class="admin-metric-value">128</div>
                            <div class="admin-metric-chip chip-green">+18% dibanding kemarin</div>
                        </div>
                        <div class="admin-metric-pill">
                            <div class="admin-metric-label">Pendapatan</div>
                            <div class="admin-metric-value">Rp 7,2jt</div>
                            <div class="admin-metric-chip chip-blue">Stabil</div>
                        </div>
                        <div class="admin-metric-pill">
                            <div class="admin-metric-label">Pengunjung</div>
                            <div class="admin-metric-value">2.430</div>
                            <div class="admin-metric-chip chip-amber">+6% traffic</div>
                        </div>
                    </div>
                </article>

                <article class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <div class="admin-card-title">Aktivitas Terbaru</div>
                            <div class="admin-card-subtitle">Pantau aktivitas penting secara cepat.</div>
                        </div>
                    </div>
                    <div class="admin-list">
                        <div class="admin-list-item">
                            <span class="admin-list-label">3 pesanan baru menunggu konfirmasi</span>
                            <span class="admin-list-meta">1 menit lalu</span>
                        </div>
                        <div class="admin-list-item">
                            <span class="admin-list-label">Stok "Produk Favorit" hampir habis</span>
                            <span class="admin-list-meta">12 menit lalu</span>
                        </div>
                        <div class="admin-list-item">
                            <span class="admin-list-label">2 pembayaran berhasil diverifikasi</span>
                            <span class="admin-list-meta">30 menit lalu</span>
                        </div>
                    </div>

                    <div class="admin-quick-actions">
                        <button class="admin-action-btn" type="button">
                            <span>Tambah Produk</span>
                            <span>Buka form produk baru</span>
                        </button>
                        <button class="admin-action-btn" type="button">
                            <span>Lihat Pesanan</span>
                            <span>Kelola pesanan terbaru</span>
                        </button>
                        <button class="admin-action-btn" type="button">
                            <span>Kelola Stok</span>
                            <span>Perbarui ketersediaan</span>
                        </button>
                        <button class="admin-action-btn" type="button">
                            <span>Pengaturan</span>
                            <span>Atur preferensi toko</span>
                        </button>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
