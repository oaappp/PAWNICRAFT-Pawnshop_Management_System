<?php
// Top bar title
$pageTitle = 'Customers';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

require_role(['admin','cashier','appraiser']);

/* Hide deprecation notices in UI */
error_reporting(E_ALL & ~E_DEPRECATED);

$db    = (new Database())->getConnection();
$error = '';
$info  = '';

function e($v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// Get ID
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['customer_id'] ?? 0);
if ($customer_id <= 0) {
    echo '<div class="alert alert-danger">Invalid customer ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Load customer
$stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = :id");
$stmt->execute([':id' => $customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    echo '<div class="alert alert-danger">Customer not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// ID type options
$idTypes = [
    'National ID',
    "Driver\'s License",
    'Passport',
    'SSS',
    'GSIS',
    'PRC ID',
    'Company ID',
    'Student ID',
    'Other'
];

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $first_name     = trim($_POST['first_name'] ?? '');
    $middle_name    = trim($_POST['middle_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $id_type        = trim($_POST['id_type'] ?? '');
    $id_number      = trim($_POST['id_number'] ?? '');
    $status         = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($first_name === '' || $last_name === '' || $contact_number === '' || $id_type === '' || $id_number === '') {
        $error = 'Please fill in all required fields.';
    } else {
        // Duplicate ID check
        $dup = $db->prepare(
            "SELECT COUNT(*) FROM customers WHERE id_number = :idnum AND customer_id <> :cid"
        );
        $dup->execute([
            ':idnum' => $id_number,
            ':cid'   => $customer_id,
        ]);
        if ($dup->fetchColumn() > 0) {
            $error = 'Another customer already uses this ID number.';
        }
    }

    // ID image upload (optional)
    $id_image_path = $customer['id_image_path'];
    if (empty($error) && !empty($_FILES['id_image']['name'])) {
        if ($_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime    = mime_content_type($_FILES['id_image']['tmp_name']);
            $size    = $_FILES['id_image']['size'];

            if (!isset($allowed[$mime])) {
                $error = 'Invalid ID image type.';
            } elseif ($size > 2 * 1024 * 1024) {
                $error = 'ID image exceeds 2MB.';
            } else {
                $ext      = $allowed[$mime];
                $filename = 'ID_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest     = UPLOAD_PATH_IDS . $filename;
                if (!move_uploaded_file($_FILES['id_image']['tmp_name'], $dest)) {
                    $error = 'Failed to upload ID image.';
                } else {
                    $id_image_path = 'assets/uploads/ids/' . $filename;
                }
            }
        } else {
            $error = 'Error uploading ID image.';
        }
    }

    if (empty($error)) {
        $old_values = $customer;

        $stmtUp = $db->prepare(
            "UPDATE customers SET
                first_name      = :first_name,
                middle_name     = :middle_name,
                last_name       = :last_name,
                date_of_birth   = :dob,
                gender          = :gender,
                contact_number  = :contact_number,
                email           = :email,
                address_line1   = :addr1,
                address_line2   = :addr2,
                city            = :city,
                province        = :province,
                postal_code     = :postal_code,
                id_type         = :id_type,
                id_number       = :id_number,
                id_image_path   = :id_image_path,
                status          = :status
             WHERE customer_id = :id"
        );
        $stmtUp->execute([
            ':first_name'     => $first_name,
            ':middle_name'    => $middle_name ?: null,
            ':last_name'      => $last_name,
            ':dob'            => $_POST['date_of_birth'] ?: null,
            ':gender'         => $_POST['gender'] ?: null,
            ':contact_number' => $contact_number,
            ':email'          => $email ?: null,
            ':addr1'          => $_POST['address_line1'] ?? null,
            ':addr2'          => $_POST['address_line2'] ?? null,
            ':city'           => $_POST['city'] ?? null,
            ':province'       => $_POST['province'] ?? null,
            ':postal_code'    => $_POST['postal_code'] ?? null,
            ':id_type'        => $id_type,
            ':id_number'      => $id_number,
            ':id_image_path'  => $id_image_path,
            ':status'         => $status,
            ':id'             => $customer_id,
        ]);

        // Reload
        $stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = :id");
        $stmt->execute([':id' => $customer_id]);
        $customer = $stmt->fetch();

        // Audit
        $db->prepare(
            "INSERT INTO audit_logs
               (user_id, action_type, table_affected, record_id, old_values, new_values, ip_address)
             VALUES
               (:uid, 'update', 'customers', :rid, :oldv, :newv, :ip)"
        )->execute([
            ':uid'  => $_SESSION['user_id'],
            ':rid'  => $customer_id,
            ':oldv' => json_encode($old_values),
            ':newv' => json_encode([
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'status'     => $status
            ]),
            ':ip'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        $info = 'Customer updated successfully.';
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Edit Customer</h3>
        <small class="text-muted">
            Update customer information or set status to Inactive to deactivate the profile.
        </small>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($info): ?>
    <div class="alert alert-success"><?php echo e($info); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
            <input type="hidden" name="customer_id" value="<?php echo (int)$customer['customer_id']; ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Customer Code</label>
                    <input type="text" class="form-control form-control-sm"
                           value="<?php echo e($customer['customer_code']); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control form-control-sm"
                           value="<?php echo e($customer['first_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control form-control-sm"
                           value="<?php echo e($customer['middle_name']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control form-control-sm"
                           value="<?php echo e($customer['last_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control form-control-sm"
                           value="<?php echo e($customer['contact_number']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="<?php echo e($customer['email']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control form-control-sm"
                           value="<?php echo e($customer['date_of_birth']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select form-select-sm">
                        <option value="">Select</option>
                        <option value="male"   <?php if($customer['gender']==='male') echo 'selected'; ?>>Male</option>
                        <option value="female" <?php if($customer['gender']==='female') echo 'selected'; ?>>Female</option>
                        <option value="other"  <?php if($customer['gender']==='other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="active"   <?php if($customer['status']==='active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if($customer['status']==='inactive') echo 'selected'; ?>>Inactive (deactivated)</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Address</label>
                    <input type="text" name="address_line1" class="form-control form-control-sm mb-1"
                           placeholder="Street / Barangay"
                           value="<?php echo e($customer['address_line1']); ?>">
                    <input type="text" name="address_line2" class="form-control form-control-sm mb-1"
                           placeholder="Subdivision / Building / Unit"
                           value="<?php echo e($customer['address_line2']); ?>">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="city" class="form-control form-control-sm"
                                   placeholder="City"
                                   value="<?php echo e($customer['city']); ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="province" class="form-control form-control-sm"
                                   placeholder="Province"
                                   value="<?php echo e($customer['province']); ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="postal_code" class="form-control form-control-sm"
                                   placeholder="Postal Code"
                                   value="<?php echo e($customer['postal_code']); ?>">
                        </div>
                    </div>
                </div>

                <hr class="mt-2 mb-2">

                <div class="col-md-4">
                    <label class="form-label">ID Type</label>
                    <select name="id_type" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        <?php foreach ($idTypes as $t): ?>
                            <option value="<?php echo e($t); ?>"
                                <?php if($customer['id_type'] === $t) echo 'selected'; ?>>
                                <?php echo e($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ID Number</label>
                    <input type="text" name="id_number" class="form-control form-control-sm"
                           value="<?php echo e($customer['id_number']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ID Image (JPG/PNG, max 2MB)</label>
                    <input type="file" name="id_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                    <?php if (!empty($customer['id_image_path'])): ?>
                        <small class="d-block mt-1">
                            Current:
                            <a href="<?php echo BASE_URL . '/' . e($customer['id_image_path']); ?>" target="_blank">View</a>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-teal btn-sm">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>