<?php
session_start();

// Simple hardcoded admin credentials
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'admin123';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        header('Location: dashboard_admin.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <link rel="stylesheet" href="../Landing Page/styles.css" />
    <style>
        /* Admin login specific overrides */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }
        .admin-auth-container {
            max-width: 420px;
            width: 100%;
            background: rgba(15, 23, 42, 0.95);
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(148, 163, 184, 0.35);
            backdrop-filter: blur(18px);
        }
        .admin-auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e5e7eb;
            margin-bottom: 0.25rem;
        }
        .admin-auth-subtitle {
            font-size: 0.9rem;
            color: #9ca3af;
            margin-bottom: 1.75rem;
        }
        .admin-input-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1.1rem;
        }
        .admin-input-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #e5e7eb;
        }
        .admin-input {
            border-radius: 0.6rem;
            border: 1px solid #1f2937;
            padding: 0.7rem 0.85rem;
            background: rgba(15, 23, 42, 0.8);
            color: #f9fafb;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .admin-input::placeholder {
            color: #6b7280;
        }
        .admin-input:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.5);
            background: rgba(15, 23, 42, 1);
        }
        .admin-submit-btn {
            width: 100%;
            border-radius: 999px;
            border: none;
            padding: 0.8rem 1rem;
            margin-top: 0.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            cursor: pointer;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #022c22;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.4);
            transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease;
        }
        .admin-submit-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow: 0 14px 35px rgba(34, 197, 94, 0.55);
        }
        .admin-submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(34, 197, 94, 0.35);
        }
        .admin-error {
            margin-bottom: 0.75rem;
            padding: 0.6rem 0.75rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(248, 113, 113, 0.5);
            background: rgba(30, 64, 175, 0.15);
            color: #fecaca;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="admin-auth-container">
        <h1 class="admin-auth-title">Admin Panel</h1>
        <p class="admin-auth-subtitle">Masuk menggunakan akun admin untuk mengelola konten.</p>

        <?php if ($error): ?>
            <div class="admin-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="admin-input-group">
                <label class="admin-input-label" for="username">Username</label>
                <input class="admin-input" type="text" id="username" name="username" placeholder="Masukkan username" required />
            </div>
            <div class="admin-input-group">
                <label class="admin-input-label" for="password">Password</label>
                <input class="admin-input" type="password" id="password" name="password" placeholder="Masukkan password" required />
            </div>
            <button type="submit" class="admin-submit-btn">Login</button>
        </form>
    </div>
</body>
</html>
