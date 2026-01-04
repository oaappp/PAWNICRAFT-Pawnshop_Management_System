<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = (new Database())->getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter username and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND status = 'active'");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];

                $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id")
                   ->execute([':id' => $user['user_id']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Pawnshop Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom dark theme -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css">

    <style>
        .auth-wrapper {
            min-height: 100vh;
            background-color: #020617; /* same as bg-main */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background-color: #020617;
            border-radius: 0.8rem;
            border: 1px solid #111827;
            padding: 2rem 2.5rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.8);
            color: #f9fafb;
        }
        .auth-brand {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .auth-brand-text {
            font-size: 1.4rem;          /* increased size/height */
            font-weight: 700;
            line-height: 1.3;
        }
        .auth-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .auth-subtitle {
            font-size: 0.85rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }
        .auth-card .form-label {
            font-size: 0.85rem;
            color: #e5e7eb;
        }
        .auth-card .form-control {
            background-color: #020617;
            border: 1px solid #111827;
            color: #f9fafb;
            font-size: 0.9rem;
        }
        .auth-card .form-control:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.15);
            background-color: #020617;
            color: #f9fafb;
        }
        .auth-footer {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body class="bg-body-dark">

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-text">
                Pawnshop Management System
            </div>
        </div>

        <div class="auth-title">Sign in</div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="bi bi-person me-1"></i> Username
                </label>
                <input type="text" id="username" name="username"
                       class="form-control" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">
                    <i class="bi bi-lock me-1"></i> Password
                </label>
                <input type="password" id="password" name="password"
                       class="form-control" required>
            </div>

            <button type="submit" class="btn btn-teal w-100 mt-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>
        </form>

        <div class="auth-footer">
            &copy; <?php echo date('Y'); ?> Pawnshop Management System.
        </div>
    </div>
</div>

</body>
</html>