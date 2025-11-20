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
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'purunikk_db'; // create this DB and required tables in phpMyAdmin

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

    $variantColors = $_POST['variant_color'] ?? [];
    $variantImages = $_FILES['variant_image'] ?? null;

    $logData = [
        'variant_color'        => $variantColors,
        'variant_image_names'  => $variantImages['name'] ?? null,
        'variant_image_errors' => $variantImages['error'] ?? null,
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

    // At least one image+color pair
    $hasAtLeastOneImage = false;
    if ($variantImages && isset($variantImages['name']) && is_array($variantImages['name'])) {
        foreach ($variantImages['name'] as $idx => $fileName) {
            $color = trim($variantColors[$idx] ?? '');
            if ($fileName !== '' && $color !== '') {
                $hasAtLeastOneImage = true;
                break;
            }
        }
    }

    if (!$hasAtLeastOneImage) {
        $errors[] = 'Tambahkan minimal satu warna produk beserta gambarnya.';
    }

    if (empty($errors)) {
        // Prepare upload directory
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                $errors[] = 'Gagal membuat direktori unggahan.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Insert product
            $stmtProduct = $conn->prepare('INSERT INTO products (name, price, description, category, created_at) VALUES (?, ?, ?, ?, NOW())');
            $priceFloat  = (float)$price;
            $stmtProduct->bind_param('sdss', $name, $priceFloat, $description, $category);
            $stmtProduct->execute();
            $productId = $stmtProduct->insert_id;
            $stmtProduct->close();

            // Insert images
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            $fileNames  = $variantImages['name'] ?? [];
            $tmpNames   = $variantImages['tmp_name'] ?? [];
            $fileErrors = $variantImages['error'] ?? [];

            $stmtImage = $conn->prepare('INSERT INTO product_images (product_id, color, image_path, is_primary) VALUES (?, ?, ?, ?)');

            $primarySet = false;

            foreach ($fileNames as $idx => $originalName) {
                $color = trim($variantColors[$idx] ?? '');

                if ($originalName === '' || $color === '') {
                    continue; // skip empty pair
                }

                if (!isset($tmpNames[$idx], $fileErrors[$idx])) {
                    continue;
                }

                if ($fileErrors[$idx] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Error uploading file for color ' . htmlspecialchars($color));
                }

                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions, true)) {
                    throw new RuntimeException('Invalid image type for color ' . htmlspecialchars($color) . '. Allowed: jpg, jpeg, png, webp.');
                }

                $safeBase  = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                $uniqueKey = bin2hex(random_bytes(4));
                $newName   = $safeBase . '_' . $uniqueKey . '.' . $ext;
                $target    = $uploadDir . $newName;

                if (!move_uploaded_file($tmpNames[$idx], $target)) {
                    throw new RuntimeException('Failed to move uploaded file for color ' . htmlspecialchars($color));
                }

                $relativePath = 'uploads/products/' . $newName; // relative from project root
                $isPrimary    = $primarySet ? 0 : 1;
                $primarySet   = true;

                $stmtImage->bind_param('issi', $productId, $color, $relativePath, $isPrimary);
                $stmtImage->execute();
            }

            $stmtImage->close();

            if (!$primarySet) {
                throw new RuntimeException('No valid product images were saved.');
            }

            $conn->commit();

            $successMessage = 'Produk berhasil disimpan.';
        } catch (Throwable $e) {
            if ($conn && $conn->errno === 0) {
                // try rollback, ignore errors here
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
            align-items: stretch;
            justify-content: center;
            padding: 32px 16px;
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
            color: rgba(249, 244, 225, 0.82);
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
            border-radius: 999px;
            border: none;
            padding: 9px 18px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #B08F70, #73512C);
            color: var(--primary-foreground);
            cursor: pointer;
            box-shadow: 0 16px 36px rgba(176, 143, 112, 0.7);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast), filter var(--transition-fast);
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
    </style>
</head>
<body>
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
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="button-primary">
                        <span>Simpan Produk</span>
                    </button>

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
        <div class="side-inner">
            <div class="pill">Studio Varian · Warna & Gambar</div>
            <h2 class="side-title">Varian Warna Produk</h2>
            <p class="side-subtitle">Lampirkan sebanyak mungkin gambar, masing-masing dengan label warna. Gambar pertama menjadi tampilan utama.</p>

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
                    <div class="metric-label">Logika warna</div>
                    <div class="metric-value">1 gambar : 1 warna</div>
                    <div class="metric-tag">
                        <span class="metric-dot"></span>
                        Gambar pertama utama
                    </div>
                </div>
            </div>

            <div class="variant-header">
                <div>
                    <div class="variant-title">Pasangan Warna & Gambar</div>
                    <div class="variant-subtitle">Beri nama warna, lalu unggah foto yang sesuai.</div>
                </div>
            </div>

            <div id="variant-list" class="variant-list"></div>

            <div class="variant-actions">
                <span class="chip">Tips: kelompokkan berdasarkan warna, bukan sudut foto.</span>
                <button type="button" class="button-add" id="add-variant">
                    <span class="icon">＋</span>
                    <span>Tambah warna lain</span>
                </button>
            </div>

            <div class="variant-footer-text">
                Berkas diunggah ke <code>uploads/products/</code> di dalam proyek Anda. Pastikan folder dapat ditulis oleh server.
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
    const variantList = document.getElementById('variant-list');
    const addVariantBtn = document.getElementById('add-variant');

    function createVariantRow() {
        const row = document.createElement('div');
        row.className = 'variant-row';

        row.innerHTML = `
            <div class="variant-color-input">
                <label>Nama warna</label>
                <div class="variant-color-input-row">
                    <span class="color-chip"></span>
                    <input type="text" name="variant_color[]" form="product-form" placeholder="Mis. Ungu Lembut" autocomplete="off">
                </div>
            </div>
            <div class="variant-upload">
                <div>Gambar warna</div>
                <div class="upload-shell">
                    <label class="upload-dropzone">
                        <div class="upload-icon">⇪</div>
                        <div>
                            <div class="upload-text-main">Seret atau pilih gambar</div>
                            <div class="upload-text-sub">JPG / PNG / WEBP · maks ~4 MB</div>
                        </div>
                        <input type="file" name="variant_image[]" form="product-form" accept="image/*" style="display: none;">
                    </label>
                    <div class="upload-preview" data-preview></div>
                </div>
            </div>
            <div class="variant-remove">
                <button type="button" class="icon-button" aria-label="Hapus varian">×</button>
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
            if (variantList.children.length > 1) {
                row.remove();
            }
            updateRemoveButtonsState();
        });

        return row;
    }

    function addVariantRow() {
        const row = createVariantRow();
        variantList.appendChild(row);
        updateRemoveButtonsState();
    }

    function updateRemoveButtonsState() {
        const rows = variantList.querySelectorAll('.variant-row');
        rows.forEach((row, idx) => {
            const btn = row.querySelector('.variant-remove .icon-button');
            if (rows.length === 1) {
                btn.setAttribute('disabled', 'disabled');
            } else {
                btn.removeAttribute('disabled');
            }
        });
    }

    addVariantBtn.addEventListener('click', () => {
        addVariantRow();
    });

    // Initialize with one row
    addVariantRow();

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
</script>
</body>
</html>
