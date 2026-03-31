<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$from_date = '';
$to_date = '';

switch ($filter) {
    case 'today':
        $from_date = date('Y-m-d');
        $to_date = date('Y-m-d');
        break;
    case 'week':
        $from_date = date('Y-m-d', strtotime('-7 days'));
        $to_date = date('Y-m-d');
        break;
    case 'month':
        $from_date = date('Y-m-d', strtotime('-30 days'));
        $to_date = date('Y-m-d');
        break;
    default:
        $from_date = date('Y-m-d');
        $to_date = date('Y-m-d');
}

if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
    $from_date = $_GET['from_date'];
    $to_date = $_GET['to_date'];
    $filter = 'custom';
}

$sql = "SELECT s.*, p.product_name, c.category_name 
        FROM sales s 
        LEFT JOIN products p ON s.product_id = p.product_id 
        LEFT JOIN categories c ON p.product_category = c.category_id 
        WHERE s.sale_date BETWEEN '$from_date' AND '$to_date'
        ORDER BY s.sale_date DESC";
$result = $conn->query($sql);

$total_sales = 0;
$total_amount = 0;
$category_totals = [];

while ($row = $result->fetch_assoc()) {
    $total_sales++;
    $total_amount += $row['total_amount'];
    
    $category = $row['category_name'] ?? 'Uncategorized';
    if (!isset($category_totals[$category])) {
        $category_totals[$category] = 0;
    }
    $category_totals[$category] += $row['total_amount'];
}

// Reset result pointer
$result->data_seek(0);

include '../includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Sales Report</h2>
    
    <!-- Filter Form -->
    <form method="GET" action="" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?filter=today" class="btn <?php echo $filter == 'today' ? 'btn-primary' : ''; ?>">Today</a>
            <a href="?filter=week" class="btn <?php echo $filter == 'week' ? 'btn-primary' : ''; ?>">Last 7 Days</a>
            <a href="?filter=month" class="btn <?php echo $filter == 'month' ? 'btn-primary' : ''; ?>">Last 30 Days</a>
            
            <div style="display: flex; gap: 5px;">
                <input type="date" name="from_date" class="form-control" style="width: auto;" value="<?php echo $from_date; ?>">
                <span>to</span>
                <input type="date" name="to_date" class="form-control" style="width: auto;" value="<?php echo $to_date; ?>">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </div>
    </form>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div style="background: #007bff; color: white; padding: 15px; border-radius: 5px; text-align: center;">
            <h3>Total Sales</h3>
            <div style="font-size: 24px;"><?php echo $total_sales; ?></div>
        </div>
        <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px; text-align: center;">
            <h3>Total Revenue</h3>
            <div style="font-size: 24px;">৳<?php echo number_format($total_amount, 2); ?></div>
        </div>
        <div style="background: #ffc107; color: #333; padding: 15px; border-radius: 5px; text-align: center;">
            <h3>Average Sale</h3>
            <div style="font-size: 24px;">৳<?php echo $total_sales > 0 ? number_format($total_amount / $total_sales, 2) : '0'; ?></div>
        </div>
    </div>
    
    <!-- Sales by Category -->
    <?php if (count($category_totals) > 0): ?>
    <div style="margin-bottom: 20px;">
        <h3>Sales by Category</h3>
        <table>
            <thead>
                 <tr>
                    <th>Category</th>
                    <th>Revenue</th>
                 </tr>
            </thead>
            <tbody>
                <?php foreach ($category_totals as $category => $amount): ?>
                 <tr>
                    <td><?php echo htmlspecialchars($category); ?></td>
                    <td>৳<?php echo number_format($amount, 2); ?></td>
                 </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Detailed Sales List -->
    <h3>Sales Details</h3>
    <table>
        <thead>
             <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Amount</th>
             </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                 <tr>
                    <td><?php echo $row['sale_date']; ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>৳<?php echo number_format($row['total_amount'], 2); ?></td>
                 </tr>
                <?php endwhile; ?>
            <?php else: ?>
                 <tr>
                    <td colspan="5" style="text-align: center;">No sales found for this period</td>
                 </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>