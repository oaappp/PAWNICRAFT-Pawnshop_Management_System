<?php
// Top bar title
$pageTitle = 'Customers';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role(['admin','cashier','appraiser']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db    = (new Database())->getConnection();
$error = '';

function e($v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/*
 * HANDLE POST FIRST – before any HTML output
 */
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

    if ($first_name === '' || $last_name === '' || $contact_number === '' || $id_type === '' || $id_number === '') {
        $error = 'Please fill in all required fields (name, contact, ID type and ID number).';
    } else {
        // Check duplicate ID number
        $stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE id_number = :id_number");
        $stmt->execute([':id_number' => $id_number]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'A customer with this ID number already exists.';
        }
    }

    // Handle ID image upload
    $id_image_path = null;
    if (empty($error) && !empty($_FILES['id_image']['name'])) {
        if ($_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime    = mime_content_type($_FILES['id_image']['tmp_name']);
            $size    = $_FILES['id_image']['size'];

            if (!isset($allowed[$mime])) {
                $error = 'Invalid ID image type. Only JPG/PNG allowed.';
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
        // Generate customer_code: CUST-YYYY-XXXXX
        $year = date('Y');
        $stmt = $db->query("SELECT COUNT(*) FROM customers WHERE YEAR(registration_date) = YEAR(CURDATE())");
        $count = (int)$stmt->fetchColumn() + 1;
        $customer_code = sprintf('CUST-%s-%05d', $year, $count);

        $sql = "INSERT INTO customers (
                    customer_code, first_name, middle_name, last_name,
                    date_of_birth, gender, contact_number, email,
                    address_line1, address_line2, city, province,
                    postal_code, id_type, id_number, id_image_path,
                    registration_date, status
                ) VALUES (
                    :customer_code, :first_name, :middle_name, :last_name,
                    :dob, :gender, :contact_number, :email,
                    :addr1, :addr2, :city, :province,
                    :postal_code, :id_type, :id_number, :id_image_path,
                    CURDATE(), 'active'
                )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':customer_code' => $customer_code,
            ':first_name'    => $first_name,
            ':middle_name'   => $middle_name ?: null,
            ':last_name'     => $last_name,
            ':dob'           => $_POST['date_of_birth'] ?: null,
            ':gender'        => $_POST['gender'] ?: null,
            ':contact_number'=> $contact_number,
            ':email'         => $email ?: null,
            ':addr1'         => $_POST['address_line1'] ?? null,
            ':addr2'         => $_POST['address_line2'] ?? null,
            ':city'          => $_POST['city'] ?? null,
            ':province'      => $_POST['province'] ?? null,
            ':postal_code'   => $_POST['postal_code'] ?? null,
            ':id_type'       => $id_type,
            ':id_number'     => $id_number,
            ':id_image_path' => $id_image_path,
        ]);

        $newId = $db->lastInsertId();

        // Audit log
        $db->prepare(
            "INSERT INTO audit_logs
               (user_id, action_type, table_affected, record_id, old_values, new_values, ip_address)
             VALUES
               (:user_id, 'insert', 'customers', :record_id, NULL, :new_values, :ip)"
        )->execute([
            ':user_id'    => $_SESSION['user_id'],
            ':record_id'  => $newId,
            ':new_values' => json_encode(['customer_code' => $customer_code]),
            ':ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        header('Location: list.php?created=1');
        exit;
    }
}

/*
 * AFTER LOGIC: include layout and render HTML
 */
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

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
?>

<!-- Style only for this page: specific labels white (not bold) -->
<style>
.customer-page .label-strong {
    color: #ffffff !important;
    font-weight: 400 !important; /* normal weight */
}
</style>

<div class="customer-page">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">New Customer</h3>
            <small class="text-muted">
                Register a new customer with complete personal and ID information.
            </small>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label label-strong">First Name</label>
                        <input type="text" name="first_name" class="form-control form-control-sm"
                               value="<?php echo e($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label label-strong">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control form-control-sm"
                               value="<?php echo e($_POST['middle_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label label-strong">Last Name</label>
                        <input type="text" name="last_name" class="form-control form-control-sm"
                               value="<?php echo e($_POST['last_name'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label label-strong">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control form-control-sm"
                               value="<?php echo e($_POST['contact_number'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label label-strong">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm"
                               value="<?php echo e($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label label-strong">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control form-control-sm"
                               value="<?php echo e($_POST['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label label-strong">Gender</label>
                        <select name="gender" class="form-select form-select-sm">
                            <option value="">Select</option>
                            <option value="male"   <?php if(($_POST['gender'] ?? '')==='male') echo 'selected'; ?>>Male</option>
                            <option value="female" <?php if(($_POST['gender'] ?? '')==='female') echo 'selected'; ?>>Female</option>
                            <option value="other"  <?php if(($_POST['gender'] ?? '')==='other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label label-strong">Address</label>
                        <input type="text" name="address_line1" class="form-control form-control-sm mb-1"
                               placeholder="Street / Barangay"
                               value="<?php echo e($_POST['address_line1'] ?? ''); ?>">
                        <input type="text" name="address_line2" class="form-control form-control-sm mb-1"
                               placeholder="Subdivision / Building / Unit (optional)"
                               value="<?php echo e($_POST['address_line2'] ?? ''); ?>">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="city" class="form-control form-control-sm"
                                       placeholder="City"
                                       value="<?php echo e($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="province" class="form-control form-control-sm"
                                       placeholder="Province"
                                       value="<?php echo e($_POST['province'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="postal_code" class="form-control form-control-sm"
                                       placeholder="Postal Code"
                                       value="<?php echo e($_POST['postal_code'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <hr class="mt-2 mb-2">

                    <div class="col-md-4">
                        <label class="form-label label-strong">ID Type</label>
                        <select name="id_type" class="form-select form-select-sm" required>
                            <option value="">Select</option>
                            <?php foreach ($idTypes as $t): ?>
                                <option value="<?php echo e($t); ?>"
                                    <?php if(($_POST['id_type'] ?? '') === $t) echo 'selected'; ?>>
                                    <?php echo e($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label label-strong">ID Number</label>
                        <input type="text" name="id_number" class="form-control form-control-sm"
                               value="<?php echo e($_POST['id_number'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label label-strong">ID Image (JPG/PNG, max 2MB)</label>
                        <input type="file" name="id_image" class="form-control form-control-sm"
                               accept=".jpg,.jpeg,.png">
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-teal btn-sm">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div><!-- /.customer-page -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>