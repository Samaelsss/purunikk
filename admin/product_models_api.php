<?php
// JSON API untuk mengambil semua model untuk satu produk
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'purunikk_db';

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    $productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
    if ($productId <= 0) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, product_id, model_name, image_path FROM product_models WHERE product_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();

    $models = [];
    while ($row = $res->fetch_assoc()) {
        $models[] = $row;
    }

    echo json_encode($models);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load product models',
        'message' => $e->getMessage(),
    ]);
}
