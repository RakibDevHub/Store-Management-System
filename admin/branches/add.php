<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
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
        $sql = "INSERT INTO branches (branch_name, branch_location, phone, email) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $branch_name, $branch_location, $phone, $email);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Add Branch', "Added new branch: $branch_name");
            redirect('list.php');
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Branch</h5>
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
                        <input type="text" name="branch_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" name="branch_location" class="form-control">
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
            </div>
            
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Branch
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>