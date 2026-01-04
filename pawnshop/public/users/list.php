<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

require_role(['admin']);
$db = (new Database())->getConnection();
$users = $db->query("SELECT * FROM users")->fetchAll();
?>
<h4>Users</h4>
<a href="create.php" class="btn btn-primary mb-3">New User</a>

<table class="table" id="userTable">
    <thead>
    <tr>
        <th>Username</th>
        <th>Full Name</th>
        <th>Role</th>
        <th>Status</th>
        <th>Last Login</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
            <td><?php echo htmlspecialchars($u['role']); ?></td>
            <td><?php echo htmlspecialchars($u['status']); ?></td>
            <td><?php echo htmlspecialchars($u['last_login']); ?></td>
            <td><a href="edit.php?id=<?php echo (int)$u['user_id']; ?>" class="btn btn-sm btn-secondary">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>$(function(){ $('#userTable').DataTable(); });</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>