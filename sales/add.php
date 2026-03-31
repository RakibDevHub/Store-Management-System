<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get products with stock > 0
$sql = "SELECT p.*, c.category_name FROM products p 
        LEFT JOIN categories c ON p.product_category = c.category_id 
        WHERE p.product_quantity > 0 
        ORDER BY p.product_name";
$products = $conn->query($sql);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($product_id == 0 || $quantity <= 0) {
        $error = "Please select a product and valid quantity";
    } else {
        // Get product details
        $sql = "SELECT * FROM products WHERE product_id = $product_id";
        $result = $conn->query($sql);
        $product = $result->fetch_assoc();
        
        if ($product['product_quantity'] < $quantity) {
            $error = "Insufficient stock! Available: " . $product['product_quantity'];
        } else {
            $total_amount = $product['product_price'] * $quantity;
            $new_quantity = $product['product_quantity'] - $quantity;
            $sale_date = date('Y-m-d');
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update product stock
                $update_sql = "UPDATE products SET product_quantity = $new_quantity WHERE product_id = $product_id";
                $conn->query($update_sql);
                
                // Insert into sales
                $insert_sql = "INSERT INTO sales (product_id, quantity, total_amount, sale_date) 
                               VALUES ($product_id, $quantity, $total_amount, '$sale_date')";
                $conn->query($insert_sql);
                
                $conn->commit();
                $success = "Sale recorded successfully! Total: ৳" . number_format($total_amount, 2);
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error recording sale: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Record Sale</h2>
    
    <?php if ($error): ?>
        <div class="alert-danger" style="padding: 10px; margin-bottom: 15px; background: #f8d7da; color: #721c24; border-radius: 3px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert-success" style="padding: 10px; margin-bottom: 15px; background: #d4edda; color: #155724; border-radius: 3px;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Select Product</label>
            <select name="product_id" class="form-control" required>
                <option value="">Choose Product</option>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <option value="<?php echo $product['product_id']; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?> 
                        (Stock: <?php echo $product['product_quantity']; ?> | 
                        Price: ৳<?php echo number_format($product['product_price'], 2); ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" class="form-control" min="1" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Record Sale</button>
        <a href="list.php" class="btn">View Sales</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>