<?php
// visits_api.php - simple endpoint to record and report landing page visits
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'purunikk_db';

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');

    // Ensure visits table exists
    $conn->query(
        "CREATE TABLE IF NOT EXISTS page_visits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page VARCHAR(255) NOT NULL,
            visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_agent VARCHAR(255) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            INDEX idx_visited_at (visited_at),
            INDEX idx_page_visited_at (page, visited_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // Default page key; can be overridden by ?page= query or JSON/post body field
    $pageKey = 'landing';
    if (!empty($_GET['page'])) {
        $pageKey = substr((string) $_GET['page'], 0, 200);
    }

    // If POST: record a visit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = json_decode($raw, true);
            if (is_array($data) && !empty($data['page'])) {
                $pageKey = substr((string) $data['page'], 0, 200);
            }
        }
        if (!empty($_POST['page'])) {
            $pageKey = substr((string) $_POST['page'], 0, 200);
        }

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 250) : null;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : null;

        $stmt = $conn->prepare('INSERT INTO page_visits (page, user_agent, ip_address) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $pageKey, $ua, $ip);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    // GET: return simple aggregates for the requested page
    $todayCount = 0;
    $last7Count = 0;
    $totalCount = 0;

    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM page_visits WHERE page = ? AND DATE(visited_at) = CURDATE()');
    $stmt->bind_param('s', $pageKey);
    $stmt->execute();
    $stmt->bind_result($todayCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM page_visits WHERE page = ? AND visited_at >= (NOW() - INTERVAL 7 DAY)');
    $stmt->bind_param('s', $pageKey);
    $stmt->execute();
    $stmt->bind_result($last7Count);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM page_visits WHERE page = ?');
    $stmt->bind_param('s', $pageKey);
    $stmt->execute();
    $stmt->bind_result($totalCount);
    $stmt->fetch();
    $stmt->close();

    echo json_encode([
        'page' => $pageKey,
        'today' => (int) $todayCount,
        'last7days' => (int) $last7Count,
        'total' => (int) $totalCount,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to handle visit statistics',
        'message' => $e->getMessage(),
    ]);
}
