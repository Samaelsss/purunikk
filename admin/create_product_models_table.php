<?php
// Konfigurasi database – sesuaikan jika perlu
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'purunikk_db';

// Koneksi ke MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    die('Gagal konek ke database: ' . $e->getMessage());
}

// SQL untuk membuat tabel product_models
$sql = "
CREATE TABLE IF NOT EXISTS product_models (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    model_name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    CONSTRAINT fk_product_models_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $conn->query($sql);
    echo 'Tabel product_models berhasil dicek/dibuat.';
} catch (Throwable $e) {
    echo 'Gagal membuat tabel product_models: ' . $e->getMessage();
}

$conn->close();
