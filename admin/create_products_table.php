<?php
// Konfigurasi database – sesuaikan jika perlu
$dbHost = 'sql100.hstn.me';
$dbUser = 'mseet_40337985';
$dbPass = 'Purunik123';
$dbName = 'mseet_40337985_purunik_db';

// Koneksi ke MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    die('Gagal konek ke database: ' . $e->getMessage());
}

// SQL untuk membuat tabel products
$sql = "
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    description TEXT NULL,
    category VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $conn->query($sql);
    echo 'Tabel products berhasil dicek/dibuat.';
} catch (Throwable $e) {
    echo 'Gagal membuat tabel products: ' . $e->getMessage();
}

$conn->close();

