<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login_admin.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = 'sql100.hstn.me';
$dbUser = 'mseet_40337985';
$dbPass = 'Purunik123';
$dbName = 'mseet_40337985_purunik_db';

$connectionError = '';
$successMessage  = '';
$errorMessage    = '';
$products        = [];
$conn            = null;

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    $connectionError = 'Tidak bisa terkoneksi ke database: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$connectionError && $conn) {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'delete') {
        if ($productId <= 0) {
            $errorMessage = 'ID produk tidak valid.';
        } else {
            try {
                $conn->begin_transaction();

                // Kumpulkan path gambar untuk dibersihkan setelah delete.
                $filesToRemove = [];

                $stmtMain = $conn->prepare('SELECT image_path FROM products WHERE id = ?');
                $stmtMain->bind_param('i', $productId);
                $stmtMain->execute();
                $stmtMain->bind_result($mainImage);
                if ($stmtMain->fetch() && $mainImage) {
                    $filesToRemove[] = $mainImage;
                }
                $stmtMain->close();

                $stmtVariants = $conn->prepare('SELECT image_path FROM product_variant_options WHERE product_id = ? AND image_path IS NOT NULL');
                $stmtVariants->bind_param('i', $productId);
                $stmtVariants->execute();
                $resVar = $stmtVariants->get_result();
                while ($row = $resVar->fetch_assoc()) {
                    if (!empty($row['image_path'])) {
                        $filesToRemove[] = $row['image_path'];
                    }
                }
                $stmtVariants->close();

                $stmtDelete = $conn->prepare('DELETE FROM products WHERE id = ?');
                $stmtDelete->bind_param('i', $productId);
                $stmtDelete->execute();
                $deleted = $stmtDelete->affected_rows > 0;
                $stmtDelete->close();

                $conn->commit();

                if ($deleted) {
                    $successMessage = 'Produk berhasil dihapus.';

                    // Hapus file fisik (jika ada).
                    foreach (array_unique($filesToRemove) as $relativePath) {
                        $fullPath = __DIR__ . '/../' . ltrim($relativePath, '/\\');
                        if (is_file($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                } else {
                    $errorMessage = 'Produk tidak ditemukan.';
                }
            } catch (Throwable $e) {
                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                }
                $errorMessage = 'Gagal menghapus produk: ' . $e->getMessage();
            }
        }
    }
}

if (!$connectionError && $conn) {
    try {
        $result = $conn->query('SELECT id, name, category, price, created_at FROM products ORDER BY created_at DESC');
        while ($row = $result->fetch_assoc()) {
            $row['price'] = (float)$row['price'];
            $products[]   = $row;
        }
    } catch (Throwable $e) {
        $errorMessage = 'Gagal memuat daftar produk: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk</title>
    <style>
        :root {
            --background: #F9F4E1;
            --foreground: #543310;
            --primary: #B08F70;
            --primary-foreground: #F9F4E1;
            --secondary: #73512C;
            --secondary-foreground: #F9F4E1;
            --danger: #B23F3F;
            --border: rgba(84, 51, 16, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: var(--background);
            color: var(--foreground);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .admin-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, #F9F4E1 0%, #E8CBA8 100%);
            border-bottom: 1px solid var(--border);
            z-index: 10;
        }
        .admin-topbar-title {
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .admin-nav {
            display: flex;
            gap: 10px;
        }
        .admin-nav-item {
            text-decoration: none;
            color: var(--foreground);
            padding: 9px 12px;
            border: 1px solid transparent;
            border-radius: 10px;
            transition: all 0.15s ease;
            font-size: 14px;
        }
        .admin-nav-item:hover {
            border-color: var(--primary);
            background: rgba(176, 143, 112, 0.1);
        }
        .admin-nav-item.active {
            background: var(--primary);
            color: var(--primary-foreground);
            border-color: var(--primary);
        }
        .container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px 32px;
        }
        .panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 10px 32px rgba(84, 51, 16, 0.08);
            padding: 20px;
        }
        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .panel-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }
        .flash {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 0.95rem;
        }
        .flash-success {
            background: rgba(41, 163, 81, 0.1);
            color: #2e7d32;
            border: 1px solid rgba(41, 163, 81, 0.3);
        }
        .flash-error {
            background: rgba(178, 63, 63, 0.1);
            color: #9c2b2b;
            border: 1px solid rgba(178, 63, 63, 0.3);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        th {
            background: #faf6ee;
            font-weight: 600;
        }
        tbody tr:hover {
            background: #fdf9f2;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .button {
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.14s ease;
        }
        .button-edit {
            background: var(--primary);
            color: var(--primary-foreground);
        }
        .button-edit:hover {
            opacity: 0.92;
        }
        .button-delete {
            background: var(--danger);
            color: #fff;
        }
        .button-delete:hover {
            opacity: 0.9;
        }
        .empty {
            text-align: center;
            padding: 28px 12px;
            color: #777;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(84, 51, 16, 0.06);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
        }
        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }
        @media (max-width: 400px) {
            .admin-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 12px 14px;
            }
            .admin-nav {
                flex-wrap: wrap;
                gap: 6px;
            }
            .admin-nav-item {
                padding: 7px 10px;
                font-size: 13px;
            }
            .container {
                margin: 14px auto;
                padding: 0 10px 20px;
            }
            .panel {
                padding: 14px;
            }
            th, td {
                padding: 9px 8px;
                font-size: 13px;
            }
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .actions {
                flex-direction: column;
            }
            .button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <div class="admin-topbar-title">Admin Panel</div>
        <nav class="admin-nav">
            <a href="dashboard_admin.php" class="admin-nav-item">Dashboard</a>
            <a href="product_input.php" class="admin-nav-item">Tambah Produk</a>
            <a href="manage_products.php" class="admin-nav-item active">Kelola Produk</a>
        </nav>
    </div>

    <div class="container">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="panel-title">Daftar Produk</p>
                    <div class="badge">Edit atau hapus produk dari database.</div>
                </div>
                <a href="product_input.php" class="admin-nav-item" style="padding:9px 14px;">+ Produk baru</a>
            </div>

            <?php if ($connectionError): ?>
                <div class="flash flash-error"><?= htmlspecialchars($connectionError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php elseif ($successMessage): ?>
                <div class="flash flash-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php elseif ($errorMessage): ?>
                <div class="flash flash-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="empty">Belum ada produk di database.</div>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= (int)$product['id'] ?></td>
                                    <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>Rp <?= number_format((float)$product['price'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($product['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="actions">
                                            <form method="get" action="product_edit.php">
                                                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                                <button type="submit" class="button button-edit">Edit</button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Hapus produk ini? Tindakan ini tidak bisa dibatalkan.');">
                                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="button button-delete">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

