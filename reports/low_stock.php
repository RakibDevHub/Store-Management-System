<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    redirect('../index.php');
}

$branch_filter = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

// Query for low stock products
$sql = "SELECT p.*, c.category_name, b.branch_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN branches b ON p.branch_id = b.branch_id
        WHERE p.quantity <= p.reorder_level";

if ($branch_filter > 0) {
    $sql .= " AND p.branch_id = $branch_filter";
}

$sql .= " ORDER BY (p.reorder_level - p.quantity) DESC, b.branch_name, p.product_name";
$result = $conn->query($sql);

// Get branches for filter
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Calculate totals
$total_low_stock = 0;
$total_products = 0;
$temp_result = $conn->query($sql);
while ($row = $temp_result->fetch_assoc()) {
    $total_low_stock++;
    $total_products++;
}
$temp_result->data_seek(0);

include '../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Report</h5>
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="" class="mb-4">
            <div class="row g-2">
                <div class="col-md-3">
                    <select name="branch_id" class="form-select">
                        <option value="0">All Branches</option>
                        <?php while ($branch = $branches->fetch_assoc()): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($branch_filter == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-7 text-end">
                    <a href="low_stock.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt me-1"></i>Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- Summary Alert -->
        <?php if ($total_low_stock > 0): ?>
            <div class="alert alert-warning mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-0">Low Stock Alert!</h6>
                        <p class="mb-0 small">Found <?php echo $total_low_stock; ?> product(s) with low stock that need reordering.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Low Stock Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Branch</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Need to Order</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_low_stock > 0): ?>
                        <?php while ($row = $temp_result->fetch_assoc()):
                            $need_to_order = ($row['reorder_level'] - $row['quantity']) + 10; // Order 10 extra
                            $status_class = '';
                            $status_text = '';

                            if ($row['quantity'] <= 0) {
                                $status_class = 'bg-danger';
                                $status_text = 'Out of Stock!';
                            } elseif ($row['quantity'] <= $row['reorder_level'] / 2) {
                                $status_class = 'bg-danger';
                                $status_text = 'Critical';
                            } else {
                                $status_class = 'bg-warning text-dark';
                                $status_text = 'Low Stock';
                            }
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br>
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($row['product_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name']); ?></span></td>
                                <td>
                                    <span class="fw-bold <?php echo ($row['quantity'] <= 0) ? 'text-danger' : ''; ?>">
                                        <?php echo $row['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['reorder_level']; ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $need_to_order; ?> units</span>
                                </td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <a href="../products/edit.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Update Stock
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-2 d-block"></i>
                                <p class="mb-0">All products have healthy stock levels</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Reorder Tips -->
        <div class="mt-4 p-3 bg-light rounded">
            <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Reorder Tips</h6>
            <ul class="small text-muted mb-0">
                <li>Products marked as <span class="badge bg-warning text-dark">Low Stock</span> need attention soon</li>
                <li>Products marked as <span class="badge bg-danger">Critical</span> or <span class="badge bg-danger">Out of Stock</span> need immediate reordering</li>
                <li>The "Need to Order" column suggests ordering quantity (reorder level - current stock + 10 buffer)</li>
                <li>Click "Update Stock" to add new stock when it arrives</li>
            </ul>
        </div>
    </div>
</div>

<style>
    @media print {

        .navbar,
        .card-header .btn,
        footer,
        .btn,
        .filter-form {
            display: none !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>