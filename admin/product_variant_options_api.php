<?php
// JSON API untuk mengambil varian (kategori + opsi) sebuah produk
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = 'sql100.hstn.me';
$dbUser = 'mseet_40337985';
$dbPass = 'Purunik123';
$dbName = 'mseet_40337985_purunik_db';

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    $productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
    if ($productId <= 0) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, product_id, category_name, option_name, option_price, image_path FROM product_variant_options WHERE product_id = ? ORDER BY category_name ASC, id ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();

    $variants = [];
    while ($row = $res->fetch_assoc()) {
        $row['option_price'] = isset($row['option_price']) ? (float) $row['option_price'] : 0.0;
        $variants[] = $row;
    }

    echo json_encode($variants);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load product variants',
        'message' => $e->getMessage(),
    ]);
}

