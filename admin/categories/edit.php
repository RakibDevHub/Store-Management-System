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

// Get category details
$sql = "SELECT * FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    redirect('list.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = sanitize($_POST['category_name']);
    $description = sanitize($_POST['description']);
    
    if (empty($category_name)) {
        $error = "Category name is required";
    } else {
        // Check if category name already exists for other categories
        $check_sql = "SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $category_name, $id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Category name already exists!";
        } else {
            $sql = "UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $category_name, $description, $id);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Edit Category', "Edited category: $category_name");
                redirect('list.php');
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Category</h5>
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
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" name="category_name" class="form-control" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Description</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                        <textarea name="description" class="form-control" rows="3" placeholder="Enter category description..."><?php echo htmlspecialchars($category['description']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Category
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>