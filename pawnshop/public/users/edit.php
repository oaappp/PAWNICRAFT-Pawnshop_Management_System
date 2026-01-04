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

// Get user_id (from GET or POST)
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="alert alert-danger">Invalid user ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Load current user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="alert alert-danger">User not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? '';
    $status    = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $new_password  = $_POST['password'] ?? '';
    $confirm_pass  = $_POST['password_confirm'] ?? '';

    if ($username === '' || $full_name === '' || $role === '') {
        $error = 'Username, full name and role are required.';
    } elseif (!in_array($role, ['admin','cashier','appraiser'], true)) {
        $error = 'Invalid role selected.';
    } elseif ($new_password !== '' && $new_password !== $confirm_pass) {
        $error = 'New passwords do not match.';
    } elseif ($new_password !== '' && strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        // Check unique username (except this user)
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u AND user_id <> :id");
        $check->execute([':u' => $username, ':id' => $user_id]);
        if ($check->fetchColumn() > 0) {
            $error = 'Username is already taken by another user.';
        }
    }

    if (empty($error)) {
        $old_values = $user;

        $params = [
            ':username' => $username,
            ':full_name'=> $full_name,
            ':email'    => $email ?: null,
            ':role'     => $role,
            ':status'   => $status,
            ':id'       => $user_id,
        ];

        // Build SQL depending on whether we change password
        if ($new_password !== '') {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users
                    SET username = :username,
                        full_name = :full_name,
                        email = :email,
                        role = :role,
                        status = :status,
                        password_hash = :ph
                    WHERE user_id = :id";
            $params[':ph'] = $password_hash;
        } else {
            $sql = "UPDATE users
                    SET username = :username,
                        full_name = :full_name,
                        email = :email,
                        role = :role,
                        status = :status
                    WHERE user_id = :id";
        }

        $stmtUp = $db->prepare($sql);
        $stmtUp->execute($params);

        // Reload updated user for display
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        // Audit log
        $db->prepare(
            "INSERT INTO audit_logs
               (user_id, action_type, table_affected, record_id, old_values, new_values, ip_address)
             VALUES
               (:uid, 'update', 'users', :rid, :oldv, :newv, :ip)"
        )->execute([
            ':uid'  => $_SESSION['user_id'],
            ':rid'  => $user_id,
            ':oldv' => json_encode($old_values),
            ':newv' => json_encode([
                'username'  => $username,
                'full_name' => $full_name,
                'email'     => $email,
                'role'      => $role,
                'status'    => $status,
            ]),
            ':ip'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        $info = 'User updated successfully.';
    }
}

// After logic, show page
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<h4>Edit User</h4>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($info): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
    <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">

    <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control"
               value="<?php echo htmlspecialchars($user['username']); ?>" required>
    </div>

    <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control"
               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
    </div>

    <div class="mb-3">
        <label>Email (optional)</label>
        <input type="email" name="email" class="form-control"
               value="<?php echo htmlspecialchars($user['email']); ?>">
    </div>

    <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-select" required>
            <option value="admin"     <?php if($user['role']==='admin')     echo 'selected'; ?>>Admin</option>
            <option value="cashier"   <?php if($user['role']==='cashier')   echo 'selected'; ?>>Cashier</option>
            <option value="appraiser" <?php if($user['role']==='appraiser') echo 'selected'; ?>>Appraiser</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select">
            <option value="active"   <?php if($user['status']==='active')   echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if($user['status']==='inactive') echo 'selected'; ?>>Inactive</option>
        </select>
    </div>

    <hr>
    <h6>Change Password (optional)</h6>

    <div class="mb-3">
        <label>New Password</label>
        <input type="password" name="password" class="form-control">
        <small class="text-muted">Leave blank to keep existing password.</small>
    </div>

    <div class="mb-3">
        <label>Confirm New Password</label>
        <input type="password" name="password_confirm" class="form-control">
    </div>

    <button class="btn btn-primary">Save Changes</button>
    <a href="list.php" class="btn btn-secondary">Back to List</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>