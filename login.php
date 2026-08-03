<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = 'Please enter both username and password.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE Username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['PasswordHash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['UserID'];
                $_SESSION['username']  = $user['Username'];
                $_SESSION['full_name'] = $user['FullName'];
                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Hostel Management System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
  <div class="card auth-card">
    <div class="card-body p-4 p-md-5">
      <div class="text-center mb-4">
        <i class="fa-solid fa-building-shield fa-3x text-primary mb-2"></i>
        <h4 class="fw-bold mb-0">Hostel Management System</h4>
        <p class="text-muted small">Administrator Login</p>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e): ?><div><?= clean($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= clean($token) ?>">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autofocus value="<?= clean($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-brand w-100"><i class="fa-solid fa-right-to-bracket me-1"></i> Login</button>
      </form>
      <p class="text-center text-muted small mt-4 mb-0">Default: admin / admin123</p>
    </div>
  </div>
</div>
</body>
</html>
