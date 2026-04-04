<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$branch_filter = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$sql = "SELECT s.*, p.product_name, b.branch_name, u.first_name, u.last_name 
        FROM sales s 
        LEFT JOIN products p ON s.product_id = p.product_id 
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN users u ON s.staff_id = u.user_id
        WHERE 1=1";

if ($branch_filter > 0) {
    $sql .= " AND s.branch_id = $branch_filter";
}

if (!empty($search)) {
    $sql .= " AND (s.invoice_number LIKE '%$search%' OR p.product_name LIKE '%$search%')";
}

$sql .= " ORDER BY s.sale_id DESC";
$result = $conn->query($sql);

// Get branches for filter
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Sales List (All Branches)</h5>
        <a href="add.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus me-1"></i>New Sale
        </a>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="" class="row g-2 mb-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search by invoice or product..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="branch_id" class="form-select">
                    <option value="0">All Branches</option>
                    <?php while ($b = $branches->fetch_assoc()): ?>
                        <option value="<?php echo $b['branch_id']; ?>" <?php echo ($branch_filter == $b['branch_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['branch_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="list.php" class="btn btn-secondary w-100">
                    <i class="fas fa-sync-alt me-1"></i>Reset
                </a>
            </div>
        </form>

        <!-- Sales Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Invoice</th>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Staff</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>৳<?php echo number_format($row['grand_total'], 2); ?></td>
                                <td>
                                    <?php
                                    $badge_class = ($row['payment_method'] == 'cash') ? 'success' : (($row['payment_method'] == 'card') ? 'info' : 'warning');
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?>
                                    </span>
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
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">No sales records found</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i>Record First Sale
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>