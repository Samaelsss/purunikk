<?php
// Simple JSON API to expose products for the landing page product cards
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

    // Fetch latest products with their primary image (prioritize main product image)
    $sql = "
        SELECT
            p.id,
            p.name,
            p.price,
            p.description,
            p.category,
            COALESCE(
                p.image_path,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                      AND pi.is_primary = 1
                    ORDER BY pi.id ASC
                    LIMIT 1
                ),
                (
                    SELECT pi2.image_path
                    FROM product_images pi2
                    WHERE pi2.product_id = p.id
                    ORDER BY pi2.id ASC
                    LIMIT 1
                )
            ) AS image_path
        FROM products p
        ORDER BY p.created_at DESC
        LIMIT 12
    ";

    $result = $conn->query($sql);
    $products = [];

    while ($row = $result->fetch_assoc()) {
        // Normalize price to float for easier formatting on the client
        $row['price'] = (float) $row['price'];
        $products[] = $row;
    }

    echo json_encode($products);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load products',
        'message' => $e->getMessage(),
    ]);
}

