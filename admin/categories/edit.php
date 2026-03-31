<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isAdmin()) {
    header("Location: ../../index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: list.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    
    if (empty($category_name)) {
        $error = "Category name is required";
    } else {
        $sql = "UPDATE categories SET category_name = '$category_name' WHERE category_id = $id";
        
        if ($conn->query($sql)) {
            header("Location: list.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

$sql = "SELECT * FROM categories WHERE category_id = $id";
$result = $conn->query($sql);
$category = $result->fetch_assoc();

if (!$category) {
    header("Location: list.php");
    exit();
}

include '../../includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Edit Category</h2>
    
    <?php if ($error): ?>
        <div class="alert-danger" style="padding: 10px; margin-bottom: 15px; background: #f8d7da; color: #721c24; border-radius: 3px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="category_name" class="form-control" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Update Category</button>
        <a href="list.php" class="btn">Cancel</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>