<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT s.*, p.product_name FROM sales s 
        LEFT JOIN products p ON s.product_id = p.product_id 
        ORDER BY s.sale_date DESC, s.sale_id DESC";
$result = $conn->query($sql);

$total_sales = 0;
$total_amount = 0;

include '../includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Sales List</h2>
    
     <a href="add.php" class="btn btn-success" style="margin-bottom: 15px;">+ New Sale</a>
    
     <table>
        <thead>
             <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Total Amount</th>
                <th>Sale Date</th>
             </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $total_sales++;
                $total_amount += $row['total_amount'];
            ?>
             <tr>
                 <td><?php echo $row['sale_id']; ?></td>
                 <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                 <td><?php echo $row['quantity']; ?></td>
                 <td>৳<?php echo number_format($row['total_amount'], 2); ?></td>
                 <td><?php echo $row['sale_date']; ?></td>
             </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
             <tr style="background: #f4f4f4; font-weight: bold;">
                <td colspan="3" style="text-align: right;">Total:</td>
                <td>৳<?php echo number_format($total_amount, 2); ?></td>
                <td><?php echo $total_sales; ?> sales</td>
             </tr>
        </tfoot>
     </table>
</div>

<?php include '../includes/footer.php'; ?>