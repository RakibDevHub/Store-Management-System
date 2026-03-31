<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT p.*, c.category_name FROM products p 
        LEFT JOIN categories c ON p.product_category = c.category_id 
        ORDER BY p.product_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Products</h2>
        <a href="add.php" class="btn btn-success">+ Add Product</a>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['product_id']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td><?php echo $row['product_quantity']; ?></td>
                <td>৳<?php echo number_format($row['product_price'], 2); ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $row['product_id']; ?>" class="btn btn-primary" style="padding: 5px 10px;">Edit</a>
                    <a href="delete.php?id=<?php echo $row['product_id']; ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>