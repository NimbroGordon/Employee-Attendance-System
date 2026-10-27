<?php
/**
 * login.php – Screen 1: Login
 * Checks the username and password against the users table.
 */
require_once __DIR__ . '/includes/bootstrap.php';

// Already logged in? Go straight to the dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT user_id, password_hash, full_name FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);           // prevents session fixation
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · AttendPro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="card login-card">
        <div class="brand">⏱️ AttendPro</div>
        <p class="sub">Employee Attendance Management</p>

        <?php if ($error): ?>
            <div class="flash error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn">Sign in</button>
        </form>
    </div>
</div>
</body>
</html>
