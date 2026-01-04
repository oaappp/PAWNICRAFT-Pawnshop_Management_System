<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role(['admin']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db    = (new Database())->getConnection();
$error = '';
$info  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $username   = trim($_POST['username'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = $_POST['role'] ?? '';
    $status     = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $password   = $_POST['password'] ?? '';
    $password2  = $_POST['password_confirm'] ?? '';

    if ($username === '' || $full_name === '' || $role === '' || $password === '' || $password2 === '') {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['admin','cashier','appraiser'], true)) {
        $error = 'Invalid role selected.';
    } else {
        // Check if username exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username already exists.';
        }
    }

    if (empty($error)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            "INSERT INTO users (username, password_hash, full_name, email, role, status)
             VALUES (:u, :ph, :fn, :em, :role, :status)"
        );
        $stmt->execute([
            ':u'    => $username,
            ':ph'   => $password_hash,
            ':fn'   => $full_name,
            ':em'   => $email ?: null,
            ':role' => $role,
            ':status' => $status,
        ]);

        $newId = (int)$db->lastInsertId();

        // Audit log
        $db->prepare(
            "INSERT INTO audit_logs
               (user_id, action_type, table_affected, record_id, old_values, new_values, ip_address)
             VALUES
               (:uid, 'insert', 'users', :rid, NULL, :newv, :ip)"
        )->execute([
            ':uid'  => $_SESSION['user_id'],
            ':rid'  => $newId,
            ':newv' => json_encode([
                'username' => $username,
                'role'     => $role,
                'status'   => $status,
            ]),
            ':ip'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        // Redirect to list after success
        header('Location: list.php?created=1');
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<h4>New User</h4>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

    <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control"
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
    </div>

    <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control"
               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
    </div>

    <div class="mb-3">
        <label>Email (optional)</label>
        <input type="email" name="email" class="form-control"
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>

    <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-select" required>
            <option value="">Select role</option>
            <option value="admin"     <?php if(($_POST['role'] ?? '')==='admin') echo 'selected'; ?>>Admin</option>
            <option value="cashier"   <?php if(($_POST['role'] ?? '')==='cashier') echo 'selected'; ?>>Cashier</option>
            <option value="appraiser" <?php if(($_POST['role'] ?? '')==='appraiser') echo 'selected'; ?>>Appraiser</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select">
            <option value="active"   <?php if(($_POST['status'] ?? 'active')==='active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if(($_POST['status'] ?? '')==='inactive') echo 'selected'; ?>>Inactive</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Confirm Password</label>
        <input type="password" name="password_confirm" class="form-control" required>
    </div>

    <button class="btn btn-primary">Save User</button>
    <a href="list.php" class="btn btn-secondary">Back to List</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>