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
            background: #F9F4E1;
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
            color: #F9F4E1;
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
            text-decoration: none;
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
                position: sticky;
                top: 0;
                z-index: 20;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: 0.8rem 1rem;
                gap: 0.9rem;
            }
            .admin-nav-section-label {
                display: none;
            }
            .admin-nav {
                flex-direction: row;
                gap: 0.4rem;
            }
            .admin-nav-footer {
                display: none;
            }
            .admin-main {
                padding: 1.25rem 1.1rem 1.5rem;
            }
            .admin-main-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
        @media (max-width: 400px) {
            .admin-sidebar {
                flex-direction: column;
                align-items: flex-start;
                padding: 0.75rem 0.8rem;
                gap: 0.7rem;
            }
            .admin-brand-title {
                font-size: 0.9rem;
            }
            .admin-brand-subtitle {
                font-size: 0.65rem;
            }
            .admin-nav {
                flex-wrap: wrap;
                gap: 0.35rem;
            }
            .admin-nav-item {
                padding: 0.35rem 0.7rem;
                font-size: 0.8rem;
            }
            .admin-main {
                padding: 1rem 0.75rem 1.25rem;
            }
            .admin-main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            .admin-page-title {
                font-size: 1.2rem;
            }
            .admin-page-subtitle {
                font-size: 0.8rem;
            }
            .admin-main-grid {
                gap: 0.9rem;
            }
            .admin-card {
                padding: 0.9rem 0.85rem 1rem;
            }
            .admin-metrics-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 0.6rem;
            }
            .admin-list-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.4rem;
            }
            .admin-quick-actions {
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
                    <a href="dashboard_admin.php" class="admin-nav-item active">
                        <span>Dashboard</span>
                        <span class="admin-nav-item-dot"></span>
                    </a>
                    <a href="product_input.php" class="admin-nav-item">
                        <span>Produk</span>
                    </a>
                    <a href="manage_products.php" class="admin-nav-item">
                        <span>Edit Produk</span>
                    </a>
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
                <article class="admin-card" id="visits-card">
                    <div class="admin-card-header">
                        <div>
                            <div class="admin-card-title">Statistik Pengunjung</div>
                            <div class="admin-card-subtitle">Kunjungan ke Landing Page (index.html).</div>
                        </div>
                        <span class="admin-badge">Live</span>
                    </div>
                    <div class="admin-metrics-grid">
                        <div class="admin-metric-pill">
                            <div class="admin-metric-label">Pengunjung Hari Ini</div>
                            <div class="admin-metric-value" id="visitors-today">-</div>
                            <div class="admin-metric-chip chip-green">Dihitung dari log kunjungan</div>
                        </div>
                        <div class="admin-metric-pill">
                            <div class="admin-metric-label">Pengunjung 7 Hari Terakhir</div>
                            <div class="admin-metric-value" id="visitors-week">-</div>
                            <div class="admin-metric-chip chip-blue">Termasuk hari ini</div>
                        </div>
                        <div class="admin-metric-pill">
                            <div class="admin-metric-label">Total Pengunjung</div>
                            <div class="admin-metric-value" id="visitors-total">-</div>
                            <div class="admin-metric-chip chip-amber">Sejak pencatatan dimulai</div>
                        </div>
                    </div>
                </article>

                <article class="admin-card" id="latest-products-card">
                    <div class="admin-card-header">
                        <div>
                            <div class="admin-card-title">Produk Terbaru</div>
                            <div class="admin-card-subtitle">4 produk terakhir yang ditambahkan.</div>
                        </div>
                    </div>
                    <div class="admin-list" id="latest-products-list">
                        <div class="admin-list-item">
                            <span class="admin-list-label">Memuat produk terbaru...</span>
                            <span class="admin-list-meta"></span>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Load visit statistics for landing page
    (function loadVisits(){
        var todayEl = document.getElementById('visitors-today');
        var weekEl = document.getElementById('visitors-week');
        var totalEl = document.getElementById('visitors-total');
        if (!todayEl || !weekEl || !totalEl) return;

        fetch('visits_api.php?page=landing')
            .then(function(res){ return res.json(); })
            .then(function(data){
                if (!data) return;
                todayEl.textContent = (typeof data.today !== 'undefined') ? data.today : '0';
                weekEl.textContent = (typeof data.last7days !== 'undefined') ? data.last7days : '0';
                totalEl.textContent = (typeof data.total !== 'undefined') ? data.total : '0';
            })
            .catch(function(err){ console.error('Gagal memuat statistik pengunjung:', err); });
    })();

    // Load latest 4 products from products_api.php
    (function loadLatestProducts(){
        var list = document.getElementById('latest-products-list');
        if (!list) return;

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatPrice(price) {
            var num = Number(price) || 0;
            try {
                return 'Rp' + num.toLocaleString('id-ID');
            } catch (e) {
                return 'Rp' + num.toString();
            }
        }

        fetch('products_api.php')
            .then(function(res){ return res.json(); })
            .then(function(products){
                if (!Array.isArray(products) || products.length === 0) {
                    list.innerHTML = '<div class="admin-list-item"><span class="admin-list-label">Belum ada produk di database.</span><span class="admin-list-meta"></span></div>';
                    return;
                }

                var latest = products.slice(0, 4);
                list.innerHTML = '';

                latest.forEach(function(p){
                    var name = p.name || 'Produk';
                    var category = p.category || '';
                    var priceText = formatPrice(p.price);
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'admin-list-item admin-product-link';
                    item.setAttribute('data-product-id', String(p.id));
                    item.innerHTML = '<span class="admin-list-label">' + escapeHtml(name) + '</span>' +
                                     '<span class="admin-list-meta">' + escapeHtml(category) + ' &middot; ' + escapeHtml(priceText) + '</span>';
                    list.appendChild(item);
                });

                list.addEventListener('click', function(e){
                    var target = e.target.closest('.admin-product-link');
                    if (!target) return;
                    var id = target.getAttribute('data-product-id');
                    if (!id) return;
                    window.location.href = '../Produk_detail.html?id=' + encodeURIComponent(id);
                });
            })
            .catch(function(err){
                console.error('Gagal memuat produk terbaru untuk dashboard:', err);
                list.innerHTML = '<div class="admin-list-item"><span class="admin-list-label">Gagal memuat produk terbaru.</span><span class="admin-list-meta"></span></div>';
            });
    })();
});
</script>
</body>
</html>
