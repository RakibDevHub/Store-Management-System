<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    redirect('list.php');
}

// Get user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    redirect('list.php');
}

// Get branches for dropdown
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);
    $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email already exists for other users
        $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Update user without password
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, branch_id = ?, is_active = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiii", $first_name, $last_name, $email, $phone, $role, $branch_id, $is_active, $id);

            if ($stmt->execute()) {
                // Log activity
                logActivity($_SESSION['user_id'], 'Edit User', "Edited user: $first_name $last_name");

                // Set flash message
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'text' => "User '$first_name $last_name' has been updated successfully."
                ];

                redirect('list.php');
            } else {
                $error = "Error: " . $conn->error;
            }
        }
        $check_stmt->close();
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit User</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="staff" <?php echo ($user['role'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3" id="branchField">
                    <label class="form-label">Assign Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">-- Select Branch --</option>
                        <?php
                        $branches->data_seek(0);
                        while ($branch = $branches->fetch_assoc()):
                        ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($user['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Required for staff users</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?php echo ($user['is_active'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <small class="text-muted">Inactive users cannot login</small>
                </div>

                <!-- Password Note (Read-only, cannot edit) -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="text" class="form-control" value="●●●●●●●●" disabled>
                        <span class="input-group-text bg-light">
                            <i class="fas fa-info-circle text-muted" title="Password cannot be changed here. Users can change their own password from Settings page."></i>
                        </span>
                    </div>
                    <small class="text-muted">Password cannot be changed from here. Users can change their own password from the Settings page.</small>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update User
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // Show/hide branch field based on role
    document.getElementById('role').addEventListener('change', function() {
        let branchField = document.getElementById('branchField');
        if (this.value === 'staff') {
            branchField.style.display = 'block';
        } else {
            branchField.style.display = 'none';
        }
    });

    // Trigger on page load
    if (document.getElementById('role').value === 'admin') {
        document.getElementById('branchField').style.display = 'none';
    }
</script>

<?php include '../../includes/footer.php'; ?>