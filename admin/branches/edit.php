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

// Get branch details
$sql = "SELECT * FROM branches WHERE branch_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$branch = $stmt->get_result()->fetch_assoc();

if (!$branch) {
    redirect('list.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch_name = sanitize($_POST['branch_name']);
    $branch_location = sanitize($_POST['branch_location']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);

    if (empty($branch_name)) {
        $error = "Branch name is required";
    } else {
        // Check if branch name already exists for other branches
        $check_sql = "SELECT branch_id FROM branches WHERE branch_name = ? AND branch_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $branch_name, $id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Branch name already exists!";
        } else {
            $sql = "UPDATE branches SET branch_name = ?, branch_location = ?, phone = ?, email = ? WHERE branch_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $branch_name, $branch_location, $phone, $email, $id);

            if ($stmt->execute()) {
                // Log activity
                logActivity($_SESSION['user_id'], 'Edit Branch', "Edited branch: $branch_name");

                // Set flash message
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'text' => "Branch '$branch_name' has been updated successfully."
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
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Branch</h5>
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
                    <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-store"></i></span>
                        <input type="text" name="branch_name" class="form-control" value="<?php echo htmlspecialchars($branch['branch_name']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" name="branch_location" class="form-control" value="<?php echo htmlspecialchars($branch['branch_location']); ?>">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($branch['phone']); ?>">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($branch['email']); ?>">
                    </div>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Branch
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>