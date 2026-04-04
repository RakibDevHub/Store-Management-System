<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get branches for dropdown
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : NULL;
    $phone = sanitize($_POST['phone']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (first_name, last_name, email, password, role, branch_id, phone, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssis", $first_name, $last_name, $email, $hashed_password, $role, $branch_id, $phone);

            if ($stmt->execute()) {
                // Log activity
                logActivity($_SESSION['user_id'], 'Add User', "Added new user: $first_name $last_name (Role: $role)");

                // Set flash message
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'text' => "User '$first_name $last_name' has been added successfully."
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
        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
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
                        <input type="text" name="first_name" class="form-control" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="last_name" class="form-control" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" name="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
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
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Required for staff users</small>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save User
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