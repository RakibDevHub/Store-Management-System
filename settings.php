<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                // Log activity
                logActivity($user_id, 'Change Password', "User changed their password");

                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Password Changed!',
                    'text' => 'Your password has been updated successfully. Please login again.'
                ];

                // Logout user after password change for security
                session_destroy();
                header("Location: login.php");
                exit();
            } else {
                $error = "Error updating password. Please try again.";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <!-- User Info Card - Stays at top, doesn't disappear -->
        <div class="card shadow-sm bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-circle fa-3x"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-0"><?php echo htmlspecialchars($user_name); ?></h5>
                        <p class="mb-0 small"><?php echo htmlspecialchars($user_email); ?> | Role: <?php echo ucfirst($user_role); ?></p>
                    </div>
                    <div>
                        <i class="fas fa-shield-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Change Password Form - Left Side -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Password must be at least 6 characters</small>
                        <div class="password-strength mt-2" id="passwordStrength"></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="fas fa-save me-1"></i>Change Password
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Security Tips - Right Side -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2 text-primary"></i>Password Security Tips
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Minimum Length:</strong> Use at least 6 characters
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Mix It Up:</strong> Use uppercase and lowercase letters
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Add Numbers:</strong> Include numbers for better security
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Special Characters:</strong> Use symbols like !@#$%
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Don't Share:</strong> Never share your password with anyone
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Regular Updates:</strong> Change your password regularly
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Avoid Common Passwords:</strong> Don't use "password123" or "admin123"
                    </div>
                    <div class="list-group-item bg-transparent px-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Unique Password:</strong> Use different passwords for different accounts
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
        field.setAttribute('type', type);
    }

    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('passwordStrength');

        if (password.length === 0) {
            strengthDiv.innerHTML = '';
            return;
        }

        let strength = 0;
        let message = '';
        let classColor = '';

        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        if (strength <= 2) {
            message = 'Weak Password';
            classColor = 'text-danger';
        } else if (strength <= 4) {
            message = 'Medium Password';
            classColor = 'text-warning';
        } else {
            message = 'Strong Password';
            classColor = 'text-success';
        }

        strengthDiv.innerHTML = `<small class="${classColor}"><i class="fas fa-info-circle me-1"></i>${message}</small>`;
    });

    // Password match checker
    function checkPasswordMatch() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const submitBtn = document.getElementById('submitBtn');
        const matchDiv = document.getElementById('passwordMatch');

        if (confirmPassword.length === 0) {
            matchDiv.innerHTML = '';
            submitBtn.disabled = true;
            return;
        }

        if (newPassword === confirmPassword) {
            matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</small>';
            submitBtn.disabled = false;
        } else {
            matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</small>';
            submitBtn.disabled = true;
        }
    }

    document.getElementById('new_password').addEventListener('input', checkPasswordMatch);
    document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
</script>

<style>
    .password-strength {
        font-size: 12px;
    }

    .input-group-text {
        background-color: #f8f9fa;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .opacity-50 {
        opacity: 0.5;
    }
    
    .list-group-item {
        border-left: none;
        border-right: none;
    }
    
    .list-group-item:first-child {
        border-top: none;
    }
    
    .list-group-item:last-child {
        border-bottom: none;
    }
</style>

<?php include 'includes/footer.php'; ?>