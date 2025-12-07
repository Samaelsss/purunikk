
<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login_admin.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'mseet_40337985_purunik_db';

$connectionError = '';
$successMessage  = '';
$errorMessage    = '';
$conn            = null;

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    $connectionError = 'Tidak bisa terkoneksi ke database: ' . $e->getMessage();
}

function fetchProduct(mysqli $conn, int $productId): ?array
{
    $stmt = $conn->prepare('SELECT id, name, price, description, category, image_path FROM products WHERE id = ?');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $row  = $res->fetch_assoc();
    $stmt->close();

    if ($row) {
        $row['price'] = isset($row['price']) ? (float)$row['price'] : 0.0;
    }

    return $row ?: null;
}

function fetchVariants(mysqli $conn, int $productId): array
{
    $stmt = $conn->prepare('SELECT id, category_name, option_name, option_price, image_path FROM product_variant_options WHERE product_id = ? ORDER BY category_name ASC, id ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['option_price'] = isset($row['option_price']) ? (float)$row['option_price'] : 0.0;
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

/**
 * Upload helper that mirrors product_input fallback logic.
 */
function moveImage(array $file, string $uploadDir, string $suffix, array $allowedExtensions): array
{
    if (!isset($file['name'], $file['tmp_name'])) {
        throw new RuntimeException('File upload tidak valid.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        throw new RuntimeException('Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.');
    }

    $safeBase  = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $uniqueKey = bin2hex(random_bytes(4));
    $newName   = $safeBase . $suffix . $uniqueKey . '.' . $ext;
    $target    = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('File upload tidak valid.');
    }

    $moved = move_uploaded_file($file['tmp_name'], $target);
    if (!$moved) {
        $fallbackName   = 'fallback_' . $newName;
        $fallbackTarget = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fallbackName;
        $moved          = @rename($file['tmp_name'], $fallbackTarget);
        if ($moved) {
            $newName = $fallbackName;
            $target  = $fallbackTarget;
        } else {
            $moved = @copy($file['tmp_name'], $fallbackTarget);
            if ($moved) {
                $newName = $fallbackName;
                $target  = $fallbackTarget;
                @unlink($file['tmp_name']);
            }
        }
    }

    if (!$moved) {
        throw new RuntimeException('Gagal memindahkan file upload.');
    }

    return [
        'relative' => 'uploads/products/' . $newName,
        'full'     => $target,
    ];
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header('Location: manage_products.php');
    exit;
}

$product  = (!$connectionError && $conn) ? fetchProduct($conn, $productId) : null;
$variants = (!$connectionError && $conn) ? fetchVariants($conn, $productId) : [];

if (!$product) {
    header('Location: manage_products.php');
    exit;
}

$currentVariantMap = [];
foreach ($variants as $variant) {
    $currentVariantMap[(int)$variant['id']] = $variant;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$connectionError && $conn) {
    $name        = trim($_POST['product_name'] ?? '');
    $price       = trim($_POST['product_price'] ?? '');
    $description = trim($_POST['product_description'] ?? '');
    $category    = trim($_POST['product_category'] ?? '');
    $productFile = $_FILES['product_image'] ?? null;

    $variantIds         = $_POST['variant_id'] ?? [];
    $variantCategories  = $_POST['variant_category'] ?? [];
    $variantNames       = $_POST['variant_name'] ?? [];
    $variantPrices      = $_POST['variant_price'] ?? [];
    $variantDeleteIds   = array_map('intval', $_POST['variant_delete_ids'] ?? []);
    $variantFiles       = $_FILES['variant_image'] ?? ['name' => [], 'tmp_name' => [], 'error' => []];
    $variantImageNames  = $variantFiles['name'] ?? [];
    $variantImageTmp    = $variantFiles['tmp_name'] ?? [];
    $variantImageErrors = $variantFiles['error'] ?? [];

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    $errors = [];

    if ($name === '') {
        $errors[] = 'Nama produk wajib diisi.';
    }
    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Harga produk harus berupa angka positif.';
    }
    if ($category === '') {
        $errors[] = 'Kategori produk wajib diisi.';
    }

    if ($productFile && !empty($productFile['name'])) {
        if (($productFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi error saat mengunggah gambar produk.';
        } else {
            $ext = strtolower(pathinfo($productFile['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                $errors[] = 'Format gambar produk tidak didukung.';
            }
        }
    }

    // Pre-validate variant uploads
    foreach ($variantImageNames as $idx => $fileName) {
        if ($fileName === '') {
            continue;
        }
        if (($variantImageErrors[$idx] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi error saat mengunggah gambar varian.';
            break;
        }
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            $errors[] = 'Format gambar varian tidak didukung.';
            break;
        }
    }

    // Pastikan ada minimal satu varian aktif yang tidak dihapus.
    $hasActiveVariant = false;
    foreach ($variantNames as $idx => $variantNameRaw) {
        $variantId      = (int)($variantIds[$idx] ?? 0);
        $categoryName   = trim($variantCategories[$idx] ?? '');
        $optionName     = trim($variantNameRaw ?? '');
        $priceValue     = trim($variantPrices[$idx] ?? '');
        $hasUpload      = ($variantImageNames[$idx] ?? '') !== '';
        $markedDeletion = $variantId > 0 && in_array($variantId, $variantDeleteIds, true);
        $isEmptyRow     = ($categoryName === '' && $optionName === '' && $priceValue === '' && !$hasUpload);

        if ($markedDeletion || $isEmptyRow) {
            continue;
        }

        if ($categoryName !== '' && $optionName !== '') {
            $hasActiveVariant = true;
            break;
        }
    }

    if (!$hasActiveVariant) {
        $errors[] = 'Minimal satu varian aktif wajib diisi.';
    }

    $uploadDir = __DIR__ . '/../uploads/products';
    if (empty($errors)) {
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                $errors[] = 'Gagal membuat direktori unggahan.';
            }
        }
        if (empty($errors) && !is_writable($uploadDir)) {
            @chmod($uploadDir, 0777);
        }
    }

    if (empty($errors)) {
        $uploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR);
        $newUploads = [];
        $oldFiles   = [];

        try {
            $conn->begin_transaction();

            // Main product image
            $productImagePath = $product['image_path'] ?? null;
            if ($productFile && !empty($productFile['name'])) {
                $uploadResult      = moveImage($productFile, $uploadDir, '_main_', $allowedExtensions);
                $productImagePath  = $uploadResult['relative'];
                $newUploads[]      = $uploadResult['full'];
                if (!empty($product['image_path'])) {
                    $oldFiles[] = $product['image_path'];
                }
            }

            $priceFloat = (float)$price;
            $stmtProduct = $conn->prepare('UPDATE products SET name = ?, price = ?, description = ?, category = ?, image_path = ? WHERE id = ?');
            $stmtProduct->bind_param('sdsssi', $name, $priceFloat, $description, $category, $productImagePath, $productId);
            $stmtProduct->execute();
            $stmtProduct->close();

            $variantsToDelete = [];
            $variantUpdates   = [];
            $variantInserts   = [];
            $finalVariants    = [];

            foreach ($variantNames as $idx => $variantNameRaw) {
                $variantId      = (int)($variantIds[$idx] ?? 0);
                $categoryName   = trim($variantCategories[$idx] ?? '');
                $optionName     = trim($variantNameRaw ?? '');
                $priceValue     = trim($variantPrices[$idx] ?? '');
                $hasUpload      = ($variantImageNames[$idx] ?? '') !== '';
                $markedDeletion = $variantId > 0 && in_array($variantId, $variantDeleteIds, true);
                $existingPath   = $variantId > 0 && isset($currentVariantMap[$variantId])
                    ? ($currentVariantMap[$variantId]['image_path'] ?? null)
                    : null;
                $isEmptyRow = ($categoryName === '' && $optionName === '' && $priceValue === '' && !$hasUpload && !$existingPath);

                if ($markedDeletion) {
                    $variantsToDelete[] = $variantId;
                    if ($existingPath) {
                        $oldFiles[] = $existingPath;
                    }
                    continue;
                }

                if ($isEmptyRow) {
                    continue;
                }

                if ($categoryName === '' || $optionName === '') {
                    throw new RuntimeException('Nama kategori dan opsi varian wajib diisi.');
                }

                $optionPrice = is_numeric($priceValue) ? (float)$priceValue : 0.0;
                $imagePath   = $existingPath;

                if ($hasUpload) {
                    $uploadResult = moveImage([
                        'name' => $variantImageNames[$idx],
                        'tmp_name' => $variantImageTmp[$idx] ?? '',
                        'error' => $variantImageErrors[$idx] ?? UPLOAD_ERR_NO_FILE,
                    ], $uploadDir, '_var_', $allowedExtensions);

                    $imagePath  = $uploadResult['relative'];
                    $newUploads[] = $uploadResult['full'];

                    if ($existingPath) {
                        $oldFiles[] = $existingPath;
                    }
                }

                $finalVariants[] = [
                    'category' => $categoryName,
                    'option'   => $optionName,
                    'price'    => $optionPrice,
                    'image'    => $imagePath,
                ];

                if ($variantId > 0) {
                    $variantUpdates[] = [
                        'id'       => $variantId,
                        'category' => $categoryName,
                        'name'     => $optionName,
                        'price'    => $optionPrice,
                        'image'    => $imagePath,
                    ];
                } else {
                    $variantInserts[] = [
                        'category' => $categoryName,
                        'name'     => $optionName,
                        'price'    => $optionPrice,
                        'image'    => $imagePath,
                    ];
                }
            }

            if (!empty($variantsToDelete)) {
                $stmtDeleteVariant = $conn->prepare('DELETE FROM product_variant_options WHERE id = ? AND product_id = ?');
                foreach ($variantsToDelete as $variantDeleteId) {
                    $stmtDeleteVariant->bind_param('ii', $variantDeleteId, $productId);
                    $stmtDeleteVariant->execute();
                }
                $stmtDeleteVariant->close();
            }

            if (!empty($variantUpdates)) {
                $stmtUpdateVariant = $conn->prepare('UPDATE product_variant_options SET category_name = ?, option_name = ?, option_price = ?, image_path = ? WHERE id = ? AND product_id = ?');
                foreach ($variantUpdates as $row) {
                    $stmtUpdateVariant->bind_param(
                        'ssdsii',
                        $row['category'],
                        $row['name'],
                        $row['price'],
                        $row['image'],
                        $row['id'],
                        $productId
                    );
                    $stmtUpdateVariant->execute();
                }
                $stmtUpdateVariant->close();
            }

            if (!empty($variantInserts)) {
                $stmtInsertVariant = $conn->prepare('INSERT INTO product_variant_options (product_id, category_name, option_name, option_price, image_path) VALUES (?, ?, ?, ?, ?)');
                foreach ($variantInserts as $row) {
                    $stmtInsertVariant->bind_param(
                        'issds',
                        $productId,
                        $row['category'],
                        $row['name'],
                        $row['price'],
                        $row['image']
                    );
                    $stmtInsertVariant->execute();
                }
                $stmtInsertVariant->close();
            }

            // Sinkronkan tabel product_images dan product_models
            $conn->query('DELETE FROM product_images WHERE product_id = ' . (int)$productId);
            $conn->query('DELETE FROM product_models WHERE product_id = ' . (int)$productId);

            $stmtImage = $conn->prepare('INSERT INTO product_images (product_id, color, image_path, is_primary) VALUES (?, ?, ?, ?)');
            $stmtModel = $conn->prepare('INSERT INTO product_models (product_id, model_name, image_path) VALUES (?, ?, ?)');

            $primarySet = false;
            foreach ($finalVariants as $row) {
                if (!empty($row['image']) && (stripos($row['category'], 'warna') !== false || stripos($row['category'], 'color') !== false)) {
                    $isPrimary  = $primarySet ? 0 : 1;
                    $primarySet = true;
                    $stmtImage->bind_param('issi', $productId, $row['option'], $row['image'], $isPrimary);
                    $stmtImage->execute();
                }

                if (!empty($row['image']) && stripos($row['category'], 'model') !== false) {
                    $stmtModel->bind_param('iss', $productId, $row['option'], $row['image']);
                    $stmtModel->execute();
                }
            }

            $stmtImage->close();
            $stmtModel->close();

            $conn->commit();
            $successMessage = 'Produk berhasil diperbarui.';

            foreach (array_unique($oldFiles) as $relativePath) {
                $fullPath = __DIR__ . '/../' . ltrim($relativePath, '/\\');
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Refresh data untuk ditampilkan ulang
            $product  = fetchProduct($conn, $productId);
            $variants = fetchVariants($conn, $productId);
            $currentVariantMap = [];
            foreach ($variants as $variant) {
                $currentVariantMap[(int)$variant['id']] = $variant;
            }
        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }

            foreach ($newUploads as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $errorMessage = 'Gagal memperbarui produk: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $errorMessage = implode(' ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk</title>
    <style>
        :root {
            --background: #F9F4E1;
            --foreground: #543310;
            --primary: #B08F70;
            --primary-foreground: #F9F4E1;
            --border: rgba(84, 51, 16, 0.12);
            --danger: #B23F3F;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--background);
            color: var(--foreground);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .admin-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, #F9F4E1 0%, #E8CBA8 100%);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .admin-topbar-title { font-weight: 700; letter-spacing: 0.04em; }
        .admin-nav { display: flex; gap: 10px; }
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
            box-shadow: 0 12px 36px rgba(84, 51, 16, 0.08);
            padding: 20px;
            margin-bottom: 16px;
        }
        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field label {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .input, .textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 14px;
        }
        .textarea { min-height: 96px; resize: vertical; }
        .button {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .button-primary {
            background: var(--primary);
            color: var(--primary-foreground);
        }
        .button-primary:hover { opacity: 0.93; }
        .button-ghost {
            background: transparent;
            border: 1px dashed var(--border);
            color: var(--foreground);
        }
        .button-ghost:hover { border-color: var(--primary); }
        .button-danger {
            background: var(--danger);
            color: #fff;
        }
        .section-title {
            font-weight: 700;
            margin: 0 0 10px;
        }
        .current-image {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        .variant-table {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .variant-row {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            align-items: flex-end;
        }
        .variant-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
        .pill-danger {
            background: rgba(178, 63, 63, 0.1);
            color: #9c2b2b;
            border: 1px solid rgba(178, 63, 63, 0.25);
        }
        @media (max-width: 640px) {
            .panel { padding: 16px; }
            .variant-row { grid-template-columns: 1fr; }
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
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .variant-row {
                grid-template-columns: 1fr;
            }
            .button,
            .button-ghost,
            .button-primary {
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
            <a href="manage_products.php" class="admin-nav-item">Kelola Produk</a>
            <a href="product_edit.php?id=<?= (int)$productId ?>" class="admin-nav-item active">Edit Produk</a>
        </nav>
    </div>

    <div class="container">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= (int)$productId ?>">

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <p class="panel-title">Edit Produk</p>
                        <div class="badge">Perbarui data produk dan variannya.</div>
                    </div>
                    <a href="manage_products.php" class="admin-nav-item">Kembali</a>
                </div>

                <?php if ($connectionError): ?>
                    <div class="flash flash-error"><?= htmlspecialchars($connectionError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($successMessage): ?>
                    <div class="flash flash-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php elseif ($errorMessage): ?>
                    <div class="flash flash-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="field">
                        <label>Nama produk</label>
                        <input type="text" class="input" name="product_name" value="<?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="field">
                        <label>Harga (IDR)</label>
                        <input type="number" class="input" name="product_price" min="0" step="1000" value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="field">
                        <label>Kategori</label>
                        <input type="text" class="input" name="product_category" value="<?= htmlspecialchars($product['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="field">
                        <label>Deskripsi</label>
                        <textarea name="product_description" class="textarea"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="field">
                        <label>Gambar utama</label>
                        <?php if (!empty($product['image_path'])): ?>
                            <div class="current-image">
                                <span>Gambar saat ini:</span>
                                <code><?= htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8') ?></code>
                            </div>
                        <?php else: ?>
                            <div class="current-image">Belum ada gambar utama.</div>
                        <?php endif; ?>
                        <input type="file" name="product_image" accept="image/*">
                        <small style="color:#666;">Kosongkan jika tidak ingin mengganti gambar.</small>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <p class="panel-title">Varian Produk</p>
                    <div class="badge">Maksimum kategori bebas, gunakan nama kategori untuk Warna/Model agar sinkron.</div>
                </div>

                <div class="variant-table" id="variant-rows">
                    <?php if (empty($variants)): ?>
                        <div class="variant-row">
                            <input type="hidden" name="variant_id[]" value="0">
                            <div class="field">
                                <label>Kategori</label>
                                <input type="text" class="input" name="variant_category[]" placeholder="Mis. Warna">
                            </div>
                            <div class="field">
                                <label>Nama opsi</label>
                                <input type="text" class="input" name="variant_name[]" placeholder="Mis. Merah">
                            </div>
                            <div class="field">
                                <label>Harga varian</label>
                                <input type="number" class="input" name="variant_price[]" min="0" step="1000" placeholder="0">
                            </div>
                            <div class="field">
                                <label>Ganti / tambahkan gambar</label>
                                <input type="file" name="variant_image[]" accept="image/*">
                            </div>
                            <div class="variant-actions">
                                <button type="button" class="button button-ghost remove-variant">Hapus baris</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($variants as $variant): ?>
                            <div class="variant-row">
                                <input type="hidden" name="variant_id[]" value="<?= (int)$variant['id'] ?>">
                                <div class="field">
                                    <label>Kategori</label>
                                    <input type="text" class="input" name="variant_category[]" value="<?= htmlspecialchars($variant['category_name'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="field">
                                    <label>Nama opsi</label>
                                    <input type="text" class="input" name="variant_name[]" value="<?= htmlspecialchars($variant['option_name'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="field">
                                    <label>Harga varian</label>
                                    <input type="number" class="input" name="variant_price[]" min="0" step="1000" value="<?= htmlspecialchars((string)$variant['option_price'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="field">
                                    <label>Gambar saat ini</label>
                                    <?php if (!empty($variant['image_path'])): ?>
                                        <div class="current-image"><code><?= htmlspecialchars($variant['image_path'], ENT_QUOTES, 'UTF-8') ?></code></div>
                                    <?php else: ?>
                                        <div class="current-image">Tidak ada gambar.</div>
                                    <?php endif; ?>
                                    <input type="hidden" name="variant_existing_image[]" value="<?= htmlspecialchars($variant['image_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="field">
                                    <label>Ganti gambar (opsional)</label>
                                    <input type="file" name="variant_image[]" accept="image/*">
                                </div>
                                <div class="variant-actions">
                                    <label class="badge pill-danger">
                                        <input type="checkbox" name="variant_delete_ids[]" value="<?= (int)$variant['id'] ?>">
                                        Hapus varian ini
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 12px;">
                    <button type="button" class="button button-ghost" id="add-variant">+ Tambah varian</button>
                </div>
                <div style="margin-top: 8px; color: #666; font-size: 13px;">
                    Jika nama kategori mengandung "warna" atau "color", gambar varian akan dipakai sebagai warna utama. 
                    Jika mengandung "model", gambar akan disimpan ke tabel model.
                </div>
            </div>

            <div class="panel" style="display:flex; justify-content: flex-end;">
                <button type="submit" class="button button-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <script>
        const variantRows = document.getElementById('variant-rows');
        const addVariantBtn = document.getElementById('add-variant');

        function wireRemoveButtons(scope) {
            const buttons = scope.querySelectorAll('.remove-variant');
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('.variant-row');
                    if (row) {
                        row.remove();
                    }
                });
            });
        }

        function createVariantRow() {
            const wrapper = document.createElement('div');
            wrapper.className = 'variant-row';
            wrapper.innerHTML = `
                <input type="hidden" name="variant_id[]" value="0">
                <div class="field">
                    <label>Kategori</label>
                    <input type="text" class="input" name="variant_category[]" placeholder="Mis. Warna">
                </div>
                <div class="field">
                    <label>Nama opsi</label>
                    <input type="text" class="input" name="variant_name[]" placeholder="Mis. Biru">
                </div>
                <div class="field">
                    <label>Harga varian</label>
                    <input type="number" class="input" name="variant_price[]" min="0" step="1000" placeholder="0">
                </div>
                <div class="field">
                    <label>Ganti / tambahkan gambar</label>
                    <input type="file" name="variant_image[]" accept="image/*">
                </div>
                <div class="variant-actions">
                    <button type="button" class="button button-ghost remove-variant">Hapus baris</button>
                </div>
            `;
            wireRemoveButtons(wrapper);
            return wrapper;
        }

        if (addVariantBtn && variantRows) {
            addVariantBtn.addEventListener('click', () => {
                variantRows.appendChild(createVariantRow());
            });
        }

        if (variantRows) {
            wireRemoveButtons(variantRows);
        }
    </script>
</body>
</html>
