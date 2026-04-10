<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$branch_id = getUserBranch();
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Build query
$sql = "SELECT s.*, p.product_name 
        FROM sales s 
        LEFT JOIN products p ON s.product_id = p.product_id 
        WHERE s.branch_id = $branch_id";

if (!empty($search)) {
    $sql .= " AND (s.invoice_number LIKE '%$search%' 
              OR p.product_name LIKE '%$search%')";
}

if (!empty($from_date)) {
    $sql .= " AND s.sale_date >= '$from_date'";
}

if (!empty($to_date)) {
    $sql .= " AND s.sale_date <= '$to_date'";
}

$sql .= " ORDER BY s.sale_id DESC";
$result = $conn->query($sql);

// Calculate totals
$total_sales = 0;
$total_revenue = 0;
$temp_result = $conn->query($sql);
while ($row = $temp_result->fetch_assoc()) {
    $total_sales++;
    $total_revenue += $row['grand_total'];
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Sales History (<?php echo $_SESSION['branch_name']; ?> Branch)</h5>
            <div class="d-flex gap-2">
                <a href="add.php" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i> New Sale
                </a>
                <a href="/reports/sales_report.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-chart-line me-1"></i> Sales Report
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="" class="mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by invoice or product..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Active Filters Display -->
        <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
            <div class="mb-3">
                <small class="text-muted">
                    <i class="fas fa-filter me-1"></i>
                    Active filters:
                    <?php if (!empty($search)): ?>
                        <span class="badge bg-secondary">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                    <?php if (!empty($from_date)): ?>
                        <span class="badge bg-secondary">From: <?php echo htmlspecialchars($from_date); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($to_date)): ?>
                        <span class="badge bg-secondary">To: <?php echo htmlspecialchars($to_date); ?></span>
                    <?php endif; ?>
                    (<?php echo $total_sales; ?> results found)
                </small>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">Total Transactions</h6>
                        <h3 class="mb-0"><?php echo $total_sales; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">Total Revenue</h6>
                        <h3 class="mb-0">৳<?php echo number_format($total_revenue, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Invoice #</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>৳<?php echo number_format($row['unit_price'], 2); ?></td>
                                <td>৳<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <?php
                                    $payment_badge = '';
                                    switch ($row['payment_method']) {
                                        case 'cash':
                                            $payment_badge = 'badge bg-success';
                                            break;
                                        case 'card':
                                            $payment_badge = 'badge bg-info';
                                            break;
                                        case 'mobile_banking':
                                            $payment_badge = 'badge bg-warning text-dark';
                                            break;
                                        default:
                                            $payment_badge = 'badge bg-secondary';
                                    }
                                    ?>
                                    <span class="<?php echo $payment_badge; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['sale_date'])); ?></td>
                                <td>
                                    <a href="invoice.php?invoice=<?php echo $row['invoice_number']; ?>" class="btn btn-sm btn-info" title="View Invoice">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">No sales records found</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i>Make First Sale
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .stats-card {
        transition: transform 0.3s;
        cursor: pointer;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }
</style>

<?php include '../../includes/footer.php'; ?>