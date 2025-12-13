<?php
// JSON API untuk mengambil semua gambar (warna) untuk satu produk
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

    $stmt = $conn->prepare('SELECT id, color, image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();

    $images = [];
    while ($row = $res->fetch_assoc()) {
        $images[] = $row;
    }

    echo json_encode($images);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load product images',
        'message' => $e->getMessage(),
    ]);
}

