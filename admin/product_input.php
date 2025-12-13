<?php
session_start();

// Protect page: only logged-in admin can access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login_admin.php');
    exit;
}

// ========================
// Database configuration
// ========================
// TODO: adjust these values to match your MySQL server/database
$dbHost = 'sql100.hstn.me';
$dbUser = 'mseet_40337985';
$dbPass = 'Purunik123';
$dbName = 'mseet_40337985_purunik_db'; // create this DB and required tables in phpMyAdmin

$connectionError = '';
$conn = null;

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    $connectionError = 'Cannot connect to database: ' . $e->getMessage();
}

$successMessage = '';
$errorMessage   = '';

// ========================
// Handle form submission
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$connectionError && $conn) {
    $name        = trim($_POST['product_name'] ?? '');
    $price       = trim($_POST['product_price'] ?? '');
    $description = trim($_POST['product_description'] ?? '');
    $category    = trim($_POST['product_category'] ?? '');
    $productImage = $_FILES['product_image'] ?? null;

    // Data varian generik: hingga 3 kategori, masing-masing punya banyak baris
    $variantCategoryNames    = $_POST['variant_category_name'] ?? [];
    $variantItemNames        = $_POST['variant_item_name'] ?? [];
    $variantItemPrices       = $_POST['variant_item_price'] ?? [];
    $variantItemCategoryIdxs = $_POST['variant_item_category_index'] ?? [];
    $variantFiles            = $_FILES['variant_item_image'] ?? null;

    $logData = [
        'variant_category_name'       => $variantCategoryNames,
        'variant_item_name'           => $variantItemNames,
        'variant_item_price'          => $variantItemPrices,
        'variant_item_category_index' => $variantItemCategoryIdxs,
        'variant_item_image_names'    => $variantFiles['name'] ?? null,
        'variant_item_image_errors'   => $variantFiles['error'] ?? null,
    ];
    error_log(
        'product_input debug: ' . json_encode($logData) . PHP_EOL,
        3,
        __DIR__ . '/../server.log'
    );

    // Basic validation
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
    
    // Validate product image (required)
    $productImagePath = null;
    if (!$productImage || !isset($productImage['name']) || $productImage['name'] === '') {
        $errors[] = 'Gambar produk wajib diunggah.';
    } elseif ($productImage['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error mengunggah gambar produk.';
    } else {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($productImage['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            $errors[] = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        }
    }

    // Cek minimal satu baris varian dengan nama (gambar opsional)
    $hasAtLeastOneVariant = false;
    if (is_array($variantItemNames)) {
        foreach ($variantItemNames as $itemName) {
            if (trim($itemName ?? '') !== '') {
                $hasAtLeastOneVariant = true;
                break;
            }
        }
    }

    if (!$hasAtLeastOneVariant) {
        $errors[] = 'Tambahkan minimal satu baris varian.';
    }

    // Siapkan folder upload
    if (empty($errors)) {
        $uploadDir = __DIR__ . '/../uploads/products';
        $uploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR);

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                $errors[] = 'Gagal membuat direktori unggahan.';
            }
        }

        if (empty($errors)) {
            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0777);
            }

            if (!is_writable($uploadDir)) {
                error_log(
                    'product_input upload_dir_not_writable: ' . json_encode([
                        'upload_dir'  => $uploadDir,
                        'is_dir'      => is_dir($uploadDir),
                        'is_writable' => is_writable($uploadDir),
                    ]) . PHP_EOL,
                    3,
                    __DIR__ . '/../server.log'
                );
            }

            $uploadDir = $uploadDir . DIRECTORY_SEPARATOR;
        }
    }

    // Process main product image upload
    if (empty($errors) && $productImage && $productImage['name'] !== '') {
        try {
            $ext = strtolower(pathinfo($productImage['name'], PATHINFO_EXTENSION));
            $safeBase  = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($productImage['name'], PATHINFO_FILENAME));
            $uniqueKey = bin2hex(random_bytes(4));
            $newName   = $safeBase . '_main_' . $uniqueKey . '.' . $ext;
            $target    = $uploadDir . $newName;

            if (!is_uploaded_file($productImage['tmp_name'])) {
                $errors[] = 'File gambar produk tidak valid.';
            } else {
                $moved = move_uploaded_file($productImage['tmp_name'], $target);
                if (!$moved) {
                    $fallbackName   = 'fallback_' . $newName;
                    $fallbackTarget = $uploadDir . $fallbackName;
                    $moved          = @rename($productImage['tmp_name'], $fallbackTarget);
                    if ($moved) {
                        $newName = $fallbackName;
                        $target  = $fallbackTarget;
                    } else {
                        $moved = @copy($productImage['tmp_name'], $fallbackTarget);
                        if ($moved) {
                            $newName = $fallbackName;
                            $target  = $fallbackTarget;
                            @unlink($productImage['tmp_name']);
                        }
                    }
                }

                if ($moved) {
                    $productImagePath = 'uploads/products/' . $newName;
                } else {
                    $errors[] = 'Gagal mengunggah gambar produk.';
                    error_log(
                        'product_input image_upload_failed: ' . json_encode([
                            'file' => $productImage['name'],
                            'target' => $target,
                        ]) . PHP_EOL,
                        3,
                        __DIR__ . '/../server.log'
                    );
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'Error saat mengunggah gambar: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Ensure image_path column exists
            try {
                $checkColStmt = $conn->prepare('SHOW COLUMNS FROM products WHERE Field = ?');
                $colName = 'image_path';
                $checkColStmt->bind_param('s', $colName);
                $checkColStmt->execute();
                $result = $checkColStmt->get_result();
                if ($result->num_rows === 0) {
                    $conn->query('ALTER TABLE products ADD COLUMN image_path VARCHAR(255) DEFAULT NULL');
                }
                $checkColStmt->close();
            } catch (Throwable $e) {
                // If check fails, try to add the column anyway
                @$conn->query('ALTER TABLE products ADD COLUMN image_path VARCHAR(255) DEFAULT NULL');
            }

            // Insert product with image_path
            $stmtProduct = $conn->prepare('INSERT INTO products (name, price, description, category, image_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $priceFloat  = (float)$price;
            $stmtProduct->bind_param('sdsss', $name, $priceFloat, $description, $category, $productImagePath);
            $stmtProduct->execute();
            $productId = $stmtProduct->insert_id;
            $stmtProduct->close();

            // Siapkan upload varian + tabel product_variant_options
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            $fileNames  = $variantFiles['name'] ?? [];
            $tmpNames   = $variantFiles['tmp_name'] ?? [];
            $fileErrors = $variantFiles['error'] ?? [];

            $stmtImage   = $conn->prepare('INSERT INTO product_images (product_id, color, image_path, is_primary) VALUES (?, ?, ?, ?)');
            $stmtModel   = $conn->prepare('INSERT INTO product_models (product_id, model_name, image_path) VALUES (?, ?, ?)');
            $stmtVariant = $conn->prepare('INSERT INTO product_variant_options (product_id, category_name, option_name, option_price, image_path) VALUES (?, ?, ?, ?, ?)');

            $primarySet = false;

            foreach ($fileNames as $idx => $originalName) {
                $categoryIndex = (int)($variantItemCategoryIdxs[$idx] ?? 0);
                $categoryName  = trim($variantCategoryNames[$categoryIndex] ?? '');
                $optionName    = trim($variantItemNames[$idx] ?? '');
                $rawPrice      = trim($variantItemPrices[$idx] ?? '');
                $optionPrice   = is_numeric($rawPrice) ? (float)$rawPrice : 0.0;

                if ($optionName === '' || $categoryName === '') {
                    continue;
                }

                $relativePath = null;

                // Jika ada file upload, proses gambar (opsional)
                if ($originalName !== '' && isset($tmpNames[$idx], $fileErrors[$idx])) {
                    if ($fileErrors[$idx] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Error uploading file for variant ' . htmlspecialchars($optionName));
                    }

                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions, true)) {
                        throw new RuntimeException('Invalid image type for variant ' . htmlspecialchars($optionName) . '. Allowed: jpg, jpeg, png, webp.');
                    }

                    $safeBase  = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $uniqueKey = bin2hex(random_bytes(4));
                    $newName   = $safeBase . '_var_' . $uniqueKey . '.' . $ext;
                    $target    = $uploadDir . $newName;

                    if (!is_uploaded_file($tmpNames[$idx])) {
                        throw new RuntimeException('Temporary upload file not valid for variant ' . htmlspecialchars($optionName));
                    }

                    $finalName = $newName;
                    $moved     = move_uploaded_file($tmpNames[$idx], $target);
                    if (!$moved) {
                        $fallbackName   = 'fallback_' . $newName;
                        $fallbackTarget = $uploadDir . $fallbackName;
                        $moved          = @rename($tmpNames[$idx], $fallbackTarget);
                        if ($moved) {
                            $finalName = $fallbackName;
                            $target    = $fallbackTarget;
                        } else {
                            $moved = @copy($tmpNames[$idx], $fallbackTarget);
                            if ($moved) {
                                $finalName = $fallbackName;
                                $target    = $fallbackTarget;
                                @unlink($tmpNames[$idx]);
                            }
                        }
                    }

                    if (!$moved) {
                        error_log(
                            'product_input upload_error: ' . json_encode([
                                'category_name'    => $categoryName,
                                'option_name'      => $optionName,
                                'tmp_name'         => $tmpNames[$idx],
                                'target'           => $target,
                                'upload_dir'       => $uploadDir,
                                'is_dir'           => is_dir($uploadDir),
                                'is_writable'      => is_writable($uploadDir),
                                'file_exists_tmp'  => file_exists($tmpNames[$idx]),
                            ]) . PHP_EOL,
                            3,
                            __DIR__ . '/../server.log'
                        );
                        continue;
                    }

                    $relativePath = 'uploads/products/' . $finalName;
                }

                // Simpan ke tabel varian generik (gambar opsional, bisa NULL)
                $stmtVariant->bind_param('issds', $productId, $categoryName, $optionName, $optionPrice, $relativePath);
                $stmtVariant->execute();

                // Kompatibilitas: jika kategori mengandung "warna" atau "color" dan ada gambar, isi product_images
                if ($relativePath !== null && (stripos($categoryName, 'warna') !== false || stripos($categoryName, 'color') !== false)) {
                    $isPrimary  = $primarySet ? 0 : 1;
                    $primarySet = true;
                    $colorLabel = $optionName;
                    $stmtImage->bind_param('issi', $productId, $colorLabel, $relativePath, $isPrimary);
                    $stmtImage->execute();
                }

                // Kompatibilitas: jika kategori mengandung "model" dan ada gambar, isi product_models
                if ($relativePath !== null && stripos($categoryName, 'model') !== false) {
                    $stmtModel->bind_param('iss', $productId, $optionName, $relativePath);
                    $stmtModel->execute();
                }
            }

            $stmtVariant->close();
            $stmtImage->close();
            $stmtModel->close();

            if (!$primarySet) {
                error_log(
                    'product_input no_images_saved: ' . json_encode([
                        'product_id' => $productId,
                        'upload_dir' => $uploadDir,
                    ]) . PHP_EOL,
                    3,
                    __DIR__ . '/../server.log'
                );
            }

            $conn->commit();

            $successMessage = 'Produk berhasil disimpan.';
        } catch (Throwable $e) {
            if ($conn && $conn->errno === 0) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollbackErr) {
                }
            }
            $errorMessage = 'Failed to save product: ' . $e->getMessage();
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
    <title>Admin &mdash; Buat Produk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            /* Palet warna sesuai Landing Page */
            --background: #F9F4E1;
            --foreground: #543310;
            --primary: #B08F70;
            --primary-foreground: #F9F4E1;
            --secondary: #73512C;
            --secondary-foreground: #F9F4E1;
            --accent: #543310;
            --border-color: #B08F70;

            /* Variabel turunan untuk UI admin */
            --bg-gradient: linear-gradient(135deg, #F9F4E1 0%, #D7B290 100%);
            --card-bg: var(--background);
            --accent-soft: rgba(176, 143, 112, 0.12);
            --accent-strong: var(--primary);
            --border-subtle: rgba(176, 143, 112, 0.4);
            --text-main: var(--foreground);
            --text-muted: rgba(84, 51, 16, 0.7);
            --shadow-soft: 0 18px 48px rgba(84, 51, 16, 0.15);
            --radius-xl: 24px;
            --radius-lg: 18px;
            --radius-md: 12px;
            --transition-fast: 180ms ease-out;
            --transition-med: 230ms ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 32px 16px;
        }

        .admin-topbar {
            width: 100%;
            max-width: 1120px;
            margin: 0 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .admin-topbar-title {
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .admin-nav-item {
            border-radius: 0.65rem;
            padding: 0.45rem 0.9rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #F9F4E1;
            cursor: pointer;
            border: 1px solid transparent;
            background: rgba(84, 51, 16, 0.9);
            text-decoration: none;
            transition: background var(--transition-fast), border-color var(--transition-fast), color var(--transition-fast), transform var(--transition-fast);
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

        @media (max-width: 600px) {
            .admin-topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .shell {
            width: 100%;
            max-width: 1120px;
            display: grid;
            grid-template-columns: minmax(0, 3.5fr) minmax(0, 2.3fr);
            gap: 28px;
            align-items: flex-start;
        }

        @media (max-width: 960px) {

             .button-primary {
            top: 0px;
             }

            .shell {
                grid-template-columns: minmax(0, 1fr);
                gap: 20px;
            }
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(22px);
            border-radius: var(--radius-xl);
            padding: 26px 26px 22px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(255, 255, 255, 0.7);
            position: relative;
            overflow: hidden;
        }

        .submit-row {
            grid-column: 2 / 3;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-top: -12px;
            min-height: 50px;
        }

        @media (max-width: 960px) {
            .submit-row {
                grid-column: 1 / -1;
                justify-content: flex-start;
            }
        }

      
        /* Palet warna khusus untuk isi di dalam card-model-panel (tabel varian) */
        .card-model-panel .variant-row {
            background: rgba(249, 244, 225, 0.96);
            border: 1px solid rgba(176, 143, 112, 0.65);
        }

        .card-model-panel .variant-color-input label {
            color: var(--foreground);
        }

        .card-model-panel .variant-upload {
            color: var(--foreground);
        }

        .card-model-panel .upload-dropzone {
            background: rgba(84, 51, 16, 0.03);
            border-color: rgba(176, 143, 112, 0.8);
        }

        .card-model-panel .upload-dropzone:hover {
            background: rgba(84, 51, 16, 0.06);
            border-color: rgba(176, 143, 112, 0.95);
        }

        .card-model-panel .upload-text-sub {
            color: var(--text-muted);
        }

        .card-model-panel .upload-preview {
            background-color: #ffffff;
            border-color: rgba(176, 143, 112, 0.8);
        }

        .card-model-panel .variant-footer-text {
            color: var(--text-muted);
        }

        .card-model-panel .icon-button {
            background: rgba(84, 51, 16, 0.1);
            color: var(--foreground);
            box-shadow: none;
        }

        .card-model-panel .icon-button:hover {
            background: rgba(176, 143, 112, 0.22);
            box-shadow: none;
        }

        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.7), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(176, 143, 112, 0.35), transparent 60%);
            pointer-events: none;
            opacity: 0.32;
            mix-blend-mode: soft-light;
        }

        .card-inner {
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .title-block h1 {
            font-size: 1.6rem;
            margin: 0 0 4px;
            letter-spacing: -0.03em;
        }

        .title-block p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-strong);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent-strong);
            box-shadow: 0 0 0 4px rgba(230, 74, 25, 0.3);
        }

        .form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.1fr);
            gap: 24px;
            align-items: flex-start;
        }

        @media (max-width: 880px) {
            .form-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--foreground);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .field-label span.required {
            color: var(--secondary);
            font-weight: 700;
            margin-left: 4px;
        }

        .field-description {
            font-size: 0.76rem;
            color: var(--text-muted);
        }

        .input, .textarea, .select {
            width: 100%;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            padding: 9px 14px;
            font-size: 0.9rem;
            outline: none;
            background: rgba(255, 255, 255, 0.9);
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast), background var(--transition-fast), transform var(--transition-fast);
        }

        .textarea {
            border-radius: 16px;
            min-height: 110px;
            resize: vertical;
            padding-top: 10px;
        }

        .input:focus, .textarea:focus, .select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px rgba(176, 143, 112, 0.3);
            background: #ffffff;
        }

        .input::placeholder, .textarea::placeholder {
            color: var(--text-muted);
        }

        .side-panel {
            border-radius: var(--radius-xl);
            background: linear-gradient(135deg, #543310, #73512C);
            color: var(--primary-foreground);
            padding: 22px 20px 18px;
            position: relative;
            overflow: hidden;
        }

        .side-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 10% 0, rgba(176, 143, 112, 0.55), transparent 60%),
                radial-gradient(circle at 90% 100%, rgba(115, 81, 44, 0.45), transparent 65%);
            opacity: 0.9;
            mix-blend-mode: screen;
        }

        .side-inner {
            position: relative;
            z-index: 1;
        }

        .pill {
            display: inline-flex;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
            background: rgba(84, 51, 16, 0.85);
            color: var(--primary-foreground);
            border: 1px solid rgba(176, 143, 112, 0.6);
            margin-bottom: 10px;
        }

        .side-title {
            font-size: 1.15rem;
            margin: 0 0 6px;
        }

        .side-subtitle {
            margin: 0 0 16px;
            font-size: 0.83rem;
            color: rgba(249, 244, 225, 0.9);
        }

        .side-metrics {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .metric-card {
            flex: 1;
            padding: 10px 11px 9px;
            border-radius: 14px;
            background: rgba(84, 51, 16, 0.9);
            border: 1px solid rgba(176, 143, 112, 0.6);
            font-size: 0.75rem;
        }

        .metric-label {
            color: rgba(249, 244, 225, 0.8);
            margin-bottom: 4px;
        }

        .metric-value {
            font-size: 0.9rem;
        }

        .metric-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            color: var(--primary-foreground);
        }

        .metric-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #B08F70;
        }

        .variant-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .variant-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .variant-subtitle {
            font-size: 0.76rem;
            color: var(--foreground);
        }

        .variant-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .variant-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: stretch;
            padding: 10px 11px;
            border-radius: 16px;
            background: rgba(84, 51, 16, 0.9);
            border: 1px solid rgba(176, 143, 112, 0.6);
            position: relative;
        }

        .variant-categories {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 6px;
        }

        .variant-category-block {
            border-radius: 18px;
            background: rgba(249, 244, 225, 0.96);
            border: 1px solid rgba(176, 143, 112, 0.65);
            padding: 10px 11px 9px;
        }

        .variant-category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .variant-category-title {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .variant-category-title label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .variant-category-title input[type="text"] {
            width: 100%;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            padding: 6px 10px;
            font-size: 0.82rem;
            outline: none;
            background: #ffffff;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast), background var(--transition-fast);
        }

        .variant-category-title input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px rgba(176, 143, 112, 0.35);
            background: #ffffff;
        }

        .variant-price-input {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 0.78rem;
            color: var(--foreground);
        }

        .variant-price-input label {
            font-weight: 500;
        }

        .variant-price-input input[type="number"] {
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            padding: 6px 10px;
            font-size: 0.82rem;
            outline: none;
            background: #ffffff;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast), background var(--transition-fast);
        }

        .variant-price-input input[type="number"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px rgba(176, 143, 112, 0.35);
            background: #ffffff;
        }

        .variant-color-input {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.78rem;
        }

        .variant-color-input label {
            color: var(--primary-foreground);
        }

        .variant-color-input-row {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .variant-color-input-row input[type="text"] {
            flex: 1;
            padding: 6px 9px;
            border-radius: 999px;
            border: 1px solid rgba(176, 143, 112, 0.7);
            background: rgba(84, 51, 16, 0.9);
            color: var(--primary-foreground);
            font-size: 0.78rem;
            outline: none;
            transition: border-color var(--transition-fast), background var(--transition-fast);
        }

        .variant-color-input-row input[type="text"]::placeholder {
            color: rgba(249, 244, 225, 0.8);
        }

        .variant-color-input-row input[type="text"]:focus {
            border-color: rgba(249, 244, 225, 0.95);
            background: rgba(84, 51, 16, 0.85);
        }

        .color-chip {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            border: 2px solid rgba(84, 51, 16, 0.9);
            box-shadow: 0 0 0 2px rgba(176, 143, 112, 0.9);
            background: conic-gradient(from 180deg, #543310, #73512C, #B08F70, #D7B290, #F9F4E1, #B08F70, #73512C, #543310);
            flex-shrink: 0;
        }

        .variant-upload {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--primary-foreground);
        }

        .upload-shell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 880px) {
            .upload-shell {
                flex-wrap: wrap;
            }
        }

        .upload-dropzone {
            flex: 1;
            border-radius: 14px;
            border: 1px dashed rgba(176, 143, 112, 0.75);
            padding: 8px 9px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(84, 51, 16, 0.7);
            cursor: pointer;
            transition: background var(--transition-med), border-color var(--transition-med), transform var(--transition-fast);
        }

        .upload-dropzone:hover {
            background: rgba(84, 51, 16, 0.9);
            border-color: rgba(249, 244, 225, 0.95);
            transform: translateY(-1px);
        }

        .upload-icon {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: rgba(249, 244, 225, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-foreground);
            font-size: 14px;
        }

        .upload-text-main {
            font-size: 0.8rem;
        }

        .upload-text-sub {
            font-size: 0.72rem;
            color: rgba(249, 244, 225, 0.8);
        }

        .upload-preview {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background-size: cover;
            background-position: center;
            background-color: rgba(84, 51, 16, 0.9);
            border: 1px solid rgba(176, 143, 112, 0.7);
            flex-shrink: 0;
        }

        .variant-remove {
            align-self: flex-end;
        }

        .icon-button {
            border-radius: 999px;
            border: none;
            background: rgba(84, 51, 16, 0.9);
            color: var(--primary-foreground);
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: background var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast), opacity var(--transition-fast);
        }

        .icon-button:hover {
            background: rgba(176, 143, 112, 0.95);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(176, 143, 112, 0.6);
        }

        .icon-button[disabled] {
            opacity: 0.4;
            cursor: default;
            box-shadow: none;
            transform: none;
        }

        .variant-footer-text {
            margin-top: 8px;
            font-size: 0.72rem;
            color: rgba(249, 244, 225, 0.8);
        }

        .variant-actions {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chip {
            font-size: 0.72rem;
            color: var(--primary-foreground);
            background: rgba(84, 51, 16, 0.85);
            border-radius: 999px;
            padding: 3px 9px;
            border: 1px solid rgba(176, 143, 112, 0.7);
        }

        .button-add {
            border-radius: 999px;
            border: none;
            padding: 6px 11px;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #73512C, #B08F70);
            color: var(--primary-foreground);
            cursor: pointer;
            box-shadow: 0 12px 32px rgba(176, 143, 112, 0.5);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast), filter var(--transition-fast);
        }

        .button-add:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 42px rgba(176, 143, 112, 0.6);
            filter: brightness(1.04);
        }

        .button-add span.icon {
            font-size: 1rem;
        }

        .form-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .button-primary {
            position: sticky;
            top: 20px;
            border-radius: 999px;
            border: none;
            padding: 12px 32px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            max-width: 700px;
            background: linear-gradient(135deg, #B08F70, #73512C);
            color: var(--primary-foreground);
            cursor: pointer;
            box-shadow: 0 16px 36px rgba(176, 143, 112, 0.7);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast), filter var(--transition-fast);
            z-index: 100;
        }

        .button-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 46px rgba(176, 143, 112, 0.8);
            filter: brightness(1.04);
        }

        .button-secondary-link {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .button-secondary-link:hover {
            color: var(--foreground);
        }

        .status-pill {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(249, 244, 225, 0.9);
            border: 1px solid rgba(176, 143, 112, 0.7);
            color: var(--foreground);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-dot-success {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #B08F70;
        }

        .status-dot-error {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #73512C;
        }

        .toast {
            position: fixed;
            right: 18px;
            top: 18px;
            min-width: 260px;
            max-width: 360px;
            padding: 10px 12px;
            border-radius: 16px;
            font-size: 0.83rem;
            box-shadow: 0 18px 40px rgba(84, 51, 16, 0.45);
            display: flex;
            align-items: flex-start;
            gap: 9px;
            z-index: 40;
            animation: toast-in 240ms ease-out;
        }

        .toast-success {
            background: #F9F4E1;
            border: 1px solid #B08F70;
            color: #543310;
        }

        .toast-error {
            background: #F9F4E1;
            border: 1px solid #73512C;
            color: #543310;
        }

        .toast-icon {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .toast-success .toast-icon {
            background: #B08F70;
            color: #F9F4E1;
        }

        .toast-error .toast-icon {
            background: #73512C;
            color: #F9F4E1;
        }

        .toast-close {
            margin-left: auto;
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
            font-size: 13px;
            opacity: 0.7;
        }

        @keyframes toast-in {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 550px) {
            body {
                padding: 20px 10px;
            }

            .admin-topbar {
                margin: 0 0 12px;
            }

            .card,
            .side-panel,
            .card-model-panel {
                padding: 18px 16px 16px;
                border-radius: 18px;
            }

            .title-block h1 {
                font-size: 1.3rem;
            }

            .title-block p {
                font-size: 0.8rem;
            }

            .field-label {
                font-size: 0.8rem;
            }

            .field-description {
                font-size: 0.72rem;
            }

            .input,
            .textarea,
            .select {
                font-size: 0.8rem;
                padding: 8px 12px;
            }

            .side-title {
                font-size: 1rem;
            }

            .side-subtitle {
                font-size: 0.78rem;
            }

            .metric-card {
                font-size: 0.72rem;
                padding: 8px 8px 7px;
            }

            .variant-title {
                font-size: 0.82rem;
            }

            .variant-subtitle {
                font-size: 0.7rem;
            }

            .chip {
                font-size: 0.68rem;
                padding: 2px 8px;
            }

            .button-add {
                font-size: 0.72rem;
                padding: 5px 10px;
            }

            .button-primary {
                font-size: 0.8rem;
                padding: 8px 14px;
            }

            .status-pill {
                font-size: 0.7rem;
            }

            .variant-category-title label {
                font-size: 0.76rem;
            }

            .variant-category-title input[type="text"],
            .variant-price-input input[type="number"] {
                font-size: 0.76rem;
                padding: 6px 9px;
            }

            .toast {
                right: 10px;
                left: 10px;
                top: 10px;
                max-width: none;
                font-size: 0.78rem;
                padding: 9px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-topbar">
        <div class="admin-topbar-title">Admin Panel</div>
        <nav class="admin-nav">
            <a href="dashboard_admin.php" class="admin-nav-item">Dashboard</a>
            <a href="product_input.php" class="admin-nav-item active">Produk</a>
            <a href="manage_products.php" class="admin-nav-item">Kelola Produk</a>
        </nav>
    </div>
<form method="post" enctype="multipart/form-data" id="product-form" novalidate>
<div class="shell">
    <div class="card">
        <div class="card-inner">
            <div class="header">
                <div class="title-block">
                    <h1>Buat Produk</h1>
                    <p>Buat produk Purunikk baru dengan varian warna berbasis gambar.</p>
                </div>
                <div class="badge">
                    <span class="badge-dot"></span>
                    Editor Produk Langsung
                </div>
            </div>

                <div class="form-grid">
                    <div class="field-group">
                        <div class="field">
                            <div class="field-label">
                                <span>Nama produk <span class="required">*</span></span>
                            </div>
                            <input
                                type="text"
                                name="product_name"
                                class="input"
                                placeholder="Mis. Purun Slingbag Lala"
                                required
                            >
                        </div>

                        <div class="field">
                            <div class="field-label">
                                <span>Harga (IDR) <span class="required">*</span></span>
                            </div>
                            <input
                                type="number"
                                name="product_price"
                                class="input"
                                min="0"
                                step="1000"
                                placeholder="Mis. 249000"
                                required
                            >
                            <div class="field-description">Hanya angka, otomatis disimpan sebagai nilai desimal.</div>
                        </div>

                        <div class="field">
                            <div class="field-label">
                                <span>Kategori <span class="required">*</span></span>
                            </div>
                            <input
                                type="text"
                                name="product_category"
                                class="input"
                                list="category-suggestions"
                                placeholder="Contoh: Tas Selempang / Aksesori"
                                required
                            >
                            <datalist id="category-suggestions">
                                <option value="Sling Bag"></option>
                                <option value="Tote Bag"></option>
                                <option value="Pouch"></option>
                                <option value="Accessories"></option>
                            </datalist>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field">
                            <div class="field-label">
                                <span>Deskripsi</span>
                            </div>
                            <div class="field-description">Gunakan ini untuk menonjolkan cerita, bahan, dan penggunaan.</div>
                            <textarea
                                name="product_description"
                                class="textarea"
                                placeholder="Tulis cerita singkat: bahan, kapasitas, gaya, dan tips perawatan."
                            ></textarea>
                        </div>

                        <div class="field">
                            <div class="field-label">
                                <span>Gambar Produk <span class="required">*</span></span>
                            </div>
                            <div class="field-description">Upload gambar utama produk. JPG / PNG / WEBP · maks ~4 MB</div>
                            <div style="border: 2px dashed #b68a60; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" id="product-image-dropzone">
                                <div style="font-size: 32px; margin-bottom: 8px;">⇪</div>
                                <div style="font-weight: 600; margin-bottom: 4px;">Seret gambar atau klik untuk memilih</div>
                                <div style="color: #999; font-size: 13px;">JPG / PNG / WEBP · maks ~4 MB</div>
                                <input type="file" name="product_image" id="product_image" form="product-form" accept="image/*" style="display: none;" required>
                            </div>
                            <div id="product-image-preview" style="margin-top: 12px; text-align: center;"></div>
                        </div>
                    </div>
                </div>
                <div class="submit-row">
        <button type="submit" class="button-primary">
            <span>Simpan Produk</span>
        </button>
    </div>

                <div class="form-footer">
                    <div class="status-pill">
                        <span class="status-dot-success"></span>
                        Data disimpan di database MySQL Anda.
                    </div>

                    <a href="dashboard_admin.php" class="button-secondary-link">
                        ← Kembali ke Dashboard
                    </a>
                </div>
        </div>
    </div>

    <div class="side-panel">
        <div class="side-header">
        <div class="side-inner">
            <div class="pill">Studio Varian · Kategori & Gambar</div>
            <h2 class="side-title">Tabel Varian Produk</h2>
            <p class="side-subtitle">Buat hingga 3 kategori (mis. Warna, Model, Ukuran), lalu isi baris varian dengan nama, harga, dan gambar.</p>

            <div class="side-metrics">
                <div class="metric-card">
                    <div class="metric-label">Kualitas media</div>
                    <div class="metric-value">JPG / PNG / WEBP</div>
                    <div class="metric-tag">
                        <span class="metric-dot"></span>
                        Direkomendasikan 1200px+
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Kategori populer</div>
                    <div class="metric-value">Warna, Model, Ukuran</div>
                    <div class="metric-tag">
                        <span class="metric-dot"></span>
                        Nama kategori bebas</div>
                </div>
            </div>
        </div>

    </div>
    </div>

    <div class="card card-model-panel">
        <div class="card-inner">
            <div class="variant-header">
                <div>
                    <div class="variant-title">Tabel Varian</div>
                    <div class="variant-subtitle">Setiap kategori punya beberapa baris: nama, harga, dan gambar. Maksimal 3 kategori.</div>
                </div>
                <button type="button" class="button-add" id="add-category">
                    <span class="icon">＋</span>
                    <span>Tambah kategori</span>
                </button>
            </div>

            <div id="variant-categories" class="variant-categories"></div>

            <div class="variant-footer-text">
                Jika nama kategori mengandung <code>Warna</code> atau <code>Color</code>, gambar juga digunakan sebagai variasi warna produk.
                Jika mengandung <code>Model</code>, gambar juga disimpan sebagai model produk.
            </div>
        </div>
    </div>

</div>
</form>

<?php if ($successMessage): ?>
    <div class="toast toast-success" id="toast">
        <div class="toast-icon">✓</div>
        <div>
            <div><strong>Berhasil disimpan</strong></div>
            <div><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button class="toast-close" onclick="dismissToast()">×</button>
    </div>
<?php elseif ($errorMessage || $connectionError): ?>
    <div class="toast toast-error" id="toast">
        <div class="toast-icon">!</div>
        <div>
            <div><strong>Terjadi kesalahan</strong></div>
            <div><?= htmlspecialchars($errorMessage ?: $connectionError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button class="toast-close" onclick="dismissToast()">×</button>
    </div>
<?php endif; ?>

<script>
    const categoriesContainer = document.getElementById('variant-categories');
    const addCategoryBtn      = document.getElementById('add-category');

    let categoryCount = 0;

    function createVariantRow(categoryIndex) {
        const row = document.createElement('div');
        row.className = 'variant-row';

        row.innerHTML = `
            <input type="hidden" name="variant_item_category_index[]" value="${categoryIndex}">
            <div class="variant-color-input">
                <label>Nama opsi</label>
                <div class="variant-color-input-row">
                    <input type="text" name="variant_item_name[]" form="product-form" placeholder="Mis. Merah / Model A" autocomplete="off">
                </div>
            </div>
            <div class="variant-price-input">
                <label>Harga varian (opsional)</label>
                <input type="number" name="variant_item_price[]" form="product-form" min="0" step="1000" placeholder="Mis. 25000">
            </div>
            <div class="variant-upload">
                <div>Gambar opsi (opsional)</div>
                <div class="upload-shell">
                    <label class="upload-dropzone">
                        <div class="upload-icon">⇪</div>
                        <div>
                            <div class="upload-text-main">Seret atau pilih gambar</div>
                            <div class="upload-text-sub">JPG / PNG / WEBP · maks ~4 MB</div>
                        </div>
                        <input type="file" name="variant_item_image[]" form="product-form" accept="image/*" style="display: none;">
                    </label>
                    <div class="upload-preview" data-preview></div>
                </div>
            </div>
            <div class="variant-remove">
                <button type="button" class="icon-button" aria-label="Hapus baris">×</button>
            </div>
        `;

        const fileInput = row.querySelector('input[type="file"]');
        const preview   = row.querySelector('[data-preview]');
        const removeBtn = row.querySelector('.variant-remove .icon-button');

        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (!file) {
                preview.style.backgroundImage = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                preview.style.backgroundImage = `url('${e.target.result}')`;
            };
            reader.readAsDataURL(file);
        });

        removeBtn.addEventListener('click', () => {
            const list = row.parentElement;
            if (!list) return;
            if (list.children.length > 1) {
                row.remove();
            }
            updateAllRemoveButtonsState();
        });

        return row;
    }

    function createCategoryBlock(categoryIndex) {
        const block = document.createElement('div');
        block.className = 'variant-category-block';
        block.dataset.categoryIndex = String(categoryIndex);

        block.innerHTML = `
            <div class="variant-category-header">
                <div class="variant-category-title">
                    <label>Nama kategori</label>
                    <input type="text" name="variant_category_name[]" form="product-form" placeholder="Mis. Warna / Model / Ukuran" autocomplete="off">
                </div>
                <button type="button" class="icon-button variant-category-remove" aria-label="Hapus kategori">×</button>
            </div>
            <div class="variant-list" data-rows></div>
            <div class="variant-actions">
                <span class="chip">Tambah baris varian untuk kategori ini.</span>
                <button type="button" class="button-add variant-add-row">
                    <span class="icon">＋</span>
                    <span>Tambah baris</span>
                </button>
            </div>
        `;

        const rowsContainer = block.querySelector('[data-rows]');
        const addRowBtn     = block.querySelector('.variant-add-row');
        const removeCatBtn  = block.querySelector('.variant-category-remove');

        if (rowsContainer && addRowBtn) {
            addRowBtn.addEventListener('click', () => {
                const row = createVariantRow(categoryIndex);
                rowsContainer.appendChild(row);
                updateAllRemoveButtonsState();
            });

            // initial one row per kategori
            const firstRow = createVariantRow(categoryIndex);
            rowsContainer.appendChild(firstRow);
        }

        if (removeCatBtn) {
            removeCatBtn.addEventListener('click', () => {
                if (categoriesContainer.children.length > 1) {
                    block.remove();
                    categoryCount = categoriesContainer.children.length;
                    updateCategoryControlsState();
                    updateAllRemoveButtonsState();
                }
            });
        }

        return block;
    }

    function addCategoryBlock() {
        if (!categoriesContainer) return;
        if (categoryCount >= 3) return;
        const idx = categoryCount;
        const block = createCategoryBlock(idx);
        categoriesContainer.appendChild(block);
        categoryCount = categoriesContainer.children.length;
        updateCategoryControlsState();
        updateAllRemoveButtonsState();
    }

    function updateCategoryControlsState() {
        if (!addCategoryBtn) return;
        addCategoryBtn.disabled = categoryCount >= 3;
    }

    function updateAllRemoveButtonsState() {
        // Setiap kategori minimal 1 baris; jika hanya 1 baris, nonaktifkan tombol hapus baris
        document.querySelectorAll('.variant-category-block').forEach(block => {
            const rows = block.querySelectorAll('.variant-row');
            rows.forEach(row => {
                const btn = row.querySelector('.variant-remove .icon-button');
                if (!btn) return;
                if (rows.length === 1) {
                    btn.setAttribute('disabled', 'disabled');
                } else {
                    btn.removeAttribute('disabled');
                }
            });
        });
    }

    if (addCategoryBtn && categoriesContainer) {
        addCategoryBtn.addEventListener('click', () => {
            addCategoryBlock();
        });
    }

    // Inisialisasi dengan satu kategori dan satu baris
    if (categoriesContainer) {
        addCategoryBlock();
    }

    function dismissToast() {
        const t = document.getElementById('toast');
        if (t) {
            t.style.opacity = '0';
            t.style.transform = 'translateY(-6px) scale(0.98)';
            setTimeout(() => t.remove(), 180);
        }
    }

    setTimeout(() => {
        const t = document.getElementById('toast');
        if (t) dismissToast();
    }, 6000);

    // Product image dropzone + preview
    const productImageDropzone = document.getElementById('product-image-dropzone');
    const productImageInput = document.getElementById('product_image');
    const productImagePreview = document.getElementById('product-image-preview');

    if (productImageDropzone && productImageInput) {
        // Click to open file picker
        productImageDropzone.addEventListener('click', () => productImageInput.click());

        // Drag and drop
        productImageDropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            productImageDropzone.style.backgroundColor = '#f5ede2';
            productImageDropzone.style.borderColor = '#b68a60';
        });

        productImageDropzone.addEventListener('dragleave', () => {
            productImageDropzone.style.backgroundColor = '';
            productImageDropzone.style.borderColor = '#b68a60';
        });

        productImageDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            productImageDropzone.style.backgroundColor = '';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                productImageInput.files = files;
                updateProductImagePreview();
            }
        });

        // File input change
        productImageInput.addEventListener('change', updateProductImagePreview);

        function updateProductImagePreview() {
            const file = productImageInput.files && productImageInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    productImagePreview.innerHTML = `
                        <img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <p style="margin-top: 8px; font-size: 13px; color: #666;">${file.name}</p>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                productImagePreview.innerHTML = '';
            }
        }
    }
</script>
</body>
</html>

