<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$is_admin = isAdmin();
$user_branch = getUserBranch();

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$branch_filter = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$from_date = '';
$to_date = '';

// Handle custom date range
if (isset($_GET['from_date']) && isset($_GET['to_date']) && !empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from_date = $_GET['from_date'];
    $to_date = $_GET['to_date'];
} else {
    // Set date range based on quick filter
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
            $from_date = date('Y-m-01');
            $to_date = date('Y-m-t');
            break;
        default:
            $from_date = date('Y-m-d');
            $to_date = date('Y-m-d');
    }
}

// Build query with role-based filtering
$sql = "SELECT s.*, p.product_name, c.category_name, b.branch_name 
        FROM sales s 
        LEFT JOIN products p ON s.product_id = p.product_id 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        WHERE DATE(s.sale_date) BETWEEN '$from_date' AND '$to_date'";

// Apply branch filter based on role
if (!$is_admin) {
    $sql .= " AND s.branch_id = $user_branch";
} elseif ($branch_filter > 0) {
    $sql .= " AND s.branch_id = $branch_filter";
}

$sql .= " ORDER BY s.sale_date DESC";
$result = $conn->query($sql);

// Calculate totals
$total_sales = 0;
$total_amount = 0;
$category_totals = [];

while ($row = $result->fetch_assoc()) {
    $total_sales++;
    $total_amount += $row['grand_total'];

    $category = $row['category_name'] ?? 'Uncategorized';
    if (!isset($category_totals[$category])) {
        $category_totals[$category] = 0;
    }
    $category_totals[$category] += $row['grand_total'];
}

// Reset result pointer
$result->data_seek(0);

// Get branches for filter (admin only)
$branches = [];
if ($is_admin) {
    $branches_result = $conn->query("SELECT * FROM branches ORDER BY branch_name");
    while ($b = $branches_result->fetch_assoc()) {
        $branches[] = $b;
    }
} else {
    // Get staff's branch name
    $branch_result = $conn->query("SELECT branch_name FROM branches WHERE branch_id = $user_branch");
    $staff_branch = $branch_result->fetch_assoc();
}

include '../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Report</h5>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="" class="mb-4" id="reportForm">
            <div class="row g-2 align-items-end">
                <?php if ($is_admin): ?>
                    <div class="col-md-2">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">All Branches</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['branch_id']; ?>" <?php echo ($branch_filter == $b['branch_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="col-md-2">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff_branch['branch_name']); ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="form-label">Quick Filter</label>
                    <select name="filter" class="form-select" id="quickFilter">
                        <option value="today" <?php echo ($filter == 'today') ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo ($filter == 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo ($filter == 'month') ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" name="apply_filter" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Apply
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="sales_report.php" class="btn btn-secondary w-100">
                        <i class="fas fa-sync-alt me-1"></i>Reset
                    </a>
                </div>
            </div>
            
            <!-- Preserve filter value when submitting -->
            <input type="hidden" name="filter" id="hiddenFilter" value="<?php echo $filter; ?>">
        </form>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">Total Transactions</h6>
                        <h3 class="mb-0"><?php echo $total_sales; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">Total Revenue</h6>
                        <h3 class="mb-0">৳<?php echo number_format($total_amount, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">Average Sale Value</h6>
                        <h3 class="mb-0">৳<?php echo $total_sales > 0 ? number_format($total_amount / $total_sales, 2) : '0.00'; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales by Category -->
        <?php if (count($category_totals) > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Sales by Category</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Revenue</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_totals as $category => $amount): ?>
                                    <?php $percentage = $total_amount > 0 ? ($amount / $total_amount) * 100 : 0; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category); ?></td>
                                        <td>৳<?php echo number_format($amount, 2); ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%;">
                                                    <?php echo round($percentage, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detailed Sales List -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Sales Details</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Product</th>
                                <th>Category</th>
                                <?php if ($is_admin): ?>
                                    <th>Branch</th>
                                <?php endif; ?>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['sale_date'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                                        <?php if ($is_admin): ?>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name']); ?></span></td>
                                        <?php endif; ?>
                                        <td><?php echo $row['quantity']; ?></td>
                                        <td>৳<?php echo number_format($row['unit_price'], 2); ?></td>
                                        <td>৳<?php echo number_format($row['grand_total'], 2); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = ($row['payment_method'] == 'cash') ? 'success' : (($row['payment_method'] == 'card') ? 'info' : 'warning');
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $is_admin ? '9' : '8'; ?>" class="text-center py-4">
                                        <i class="fas fa-chart-line fa-3x text-muted mb-2 d-block"></i>
                                        <p class="mb-0">No sales found for the selected period</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <?php if ($is_admin): ?>
                                        <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                                        <td colspan="3" class="fw-bold">৳<?php echo number_format($total_amount, 2); ?></td>
                                    <?php else: ?>
                                        <td colspan="5" class="text-end fw-bold">Grand Total:</td>
                                        <td colspan="3" class="fw-bold">৳<?php echo number_format($total_amount, 2); ?></td>
                                    <?php endif; ?>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('quickFilter').addEventListener('change', function() {
        const filter = this.value;
        const today = new Date();
        let fromDate = '';
        let toDate = '';

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        switch (filter) {
            case 'today':
                fromDate = formatDate(today);
                toDate = formatDate(today);
                break;
            case 'week':
                const weekAgo = new Date(today);
                weekAgo.setDate(today.getDate() - 7);
                fromDate = formatDate(weekAgo);
                toDate = formatDate(today);
                break;
            case 'month':
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                fromDate = formatDate(firstDay);
                toDate = formatDate(lastDay);
                break;
            default:
                return;
        }

        // Set the date inputs
        document.getElementById('from_date').value = fromDate;
        document.getElementById('to_date').value = toDate;
        
        // Set the hidden filter value
        document.getElementById('hiddenFilter').value = filter;
        
        // Submit the form
        document.getElementById('reportForm').submit();
    });
</script>

<style>
    .stats-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
</style>

<?php include '../includes/footer.php'; ?>