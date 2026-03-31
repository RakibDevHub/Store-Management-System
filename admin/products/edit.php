<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: list.php");
    exit();
}

$sql = "SELECT * FROM products WHERE product_id = $id";
$result = $conn->query($sql);
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: list.php");
    exit();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = intval($_POST['category_id']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    
    if (empty($product_name) || $category_id == 0 || $quantity < 0 || $price <= 0) {
        $error = "All fields are required and must be valid";
    } else {
        $sql = "UPDATE products SET 
                product_name = '$product_name',
                product_category = $category_id,
                product_quantity = $quantity,
                product_price = $price
                WHERE product_id = $id";
        
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
    <h2 class="card-title">Edit Product</h2>
    
    <?php if ($error): ?>
        <div class="alert-danger" style="padding: 10px; margin-bottom: 15px; background: #f8d7da; color: #721c24; border-radius: 3px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" required>
                <option value="">Select Category</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $product['product_category']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" class="form-control" value="<?php echo $product['product_quantity']; ?>" min="0" required>
        </div>
        
        <div class="form-group">
            <label>Price (BDT)</label>
            <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $product['product_price']; ?>" min="0" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Update Product</button>
        <a href="list.php" class="btn">Cancel</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>