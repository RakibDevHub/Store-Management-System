<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isAdmin()) {
    header("Location: ../../index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    
    if (empty($category_name)) {
        $error = "Category name is required";
    } else {
        $sql = "INSERT INTO categories (category_name) VALUES ('$category_name')";
        
        if ($conn->query($sql)) {
            header("Location: list.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Add Category</h2>
    
    <?php if ($error): ?>
        <div class="alert-danger" style="padding: 10px; margin-bottom: 15px; background: #f8d7da; color: #721c24; border-radius: 3px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="category_name" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Save Category</button>
        <a href="list.php" class="btn">Cancel</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>