<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$is_admin = isAdmin();
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$branch_name = $_SESSION['branch_name'] ?? 'All Branches';

// Get statistics
if ($is_admin) {
    // Admin sees all branches stats
    $branches_count = $conn->query("SELECT COUNT(*) as count FROM branches")->fetch_assoc()['count'];
    $products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $sales_count = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'];
    $users_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'")->fetch_assoc()['count'];
    $total_revenue = $conn->query("SELECT SUM(grand_total) as total FROM sales")->fetch_assoc()['total'];
} else {
    // Staff sees only their branch stats
    $branch_id = getUserBranch();
    $products_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE branch_id = $branch_id")->fetch_assoc()['count'];
    $sales_count = $conn->query("SELECT COUNT(*) as count FROM sales WHERE branch_id = $branch_id")->fetch_assoc()['count'];
    $total_revenue = $conn->query("SELECT SUM(grand_total) as total FROM sales WHERE branch_id = $branch_id")->fetch_assoc()['total'];
}

include 'includes/header.php';
?>

<!-- Welcome Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h4 class="card-title">Welcome, <?php echo $user_name; ?>!</h4>
        <p class="card-text">
            <span class="badge bg-primary"><?php echo ucfirst($_SESSION['role']); ?></span>
            <?php if (!$is_admin): ?>
                <span class="badge bg-info">Branch: <?php echo $branch_name; ?></span>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php if ($is_admin): ?>
        <div class="col-md-3 mb-3">
            <div class="card stats-card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Branches</h6>
                            <h2 class="mb-0"><?php echo $branches_count; ?></h2>
                        </div>
                        <i class="fas fa-building fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-md-3 mb-3">
        <div class="card stats-card bg-success text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Products</h6>
                        <h2 class="mb-0"><?php echo $products_count; ?></h2>
                    </div>
                    <i class="fas fa-boxes fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card bg-info text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Sales</h6>
                        <h2 class="mb-0"><?php echo $sales_count; ?></h2>
                    </div>
                    <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card bg-warning text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Revenue</h6>
                        <h2 class="mb-0">৳<?php echo number_format($total_revenue ?? 0, 2); ?></h2>
                    </div>
                    <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_admin): ?>
        <div class="col-md-3 mb-3">
            <div class="card stats-card bg-danger text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Staff Users</h6>
                            <h2 class="mb-0"><?php echo $users_count; ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<h5 class="mb-3">
    <i class="fas fa-bolt me-2"></i>Quick Actions
</h5>
<div class="row">
    <!-- Record Sale -->
    <div class="col-md-4 mb-3">
        <?php if ($is_admin): ?>
            <a href="admin/sales/add.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Record Sale</h5>
                        <p class="card-text small text-muted">Record new sales transaction</p>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <a href="staff/sales/add.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Record Sale</h5>
                        <p class="card-text small text-muted">Record new sales transaction</p>
                    </div>
                </div>
            </a>
        <?php endif; ?>
    </div>

    <!-- Add Product -->
    <div class="col-md-4 mb-3">
        <?php if ($is_admin): ?>
            <a href="admin/products/add.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-plus-circle fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Add Product</h5>
                        <p class="card-text small text-muted">Add new product to inventory</p>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <a href="staff/products/add.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-plus-circle fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Add Product</h5>
                        <p class="card-text small text-muted">Add new product to your branch</p>
                    </div>
                </div>
            </a>
        <?php endif; ?>
    </div>

    <!-- Add Category (Admin Only) -->
    <?php if ($is_admin): ?>
        <div class="col-md-4 mb-3">
            <a href="admin/categories/add.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-building fa-3x text-danger mb-3"></i>
                        <h5 class="card-title">Add Branch</h5>
                        <p class="card-text small text-muted">Add new branch</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="admin/categories/add.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-tags fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Add Category</h5>
                        <p class="card-text small text-muted">Add new product category</p>
                    </div>
                </div>
            </a>
        </div>
    <?php else: ?>
        <!-- View Products (Staff) -->
        <div class="col-md-4 mb-3">
            <a href="staff/products/list.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-boxes fa-3x text-info mb-3"></i>
                        <h5 class="card-title">View Products</h5>
                        <p class="card-text small text-muted">View products in your branch</p>
                    </div>
                </div>
            </a>
        </div>
    <?php endif; ?>

    <!-- View Sales Report (Both) -->
    <div class="col-md-4 mb-3">
        <a href="reports/sales_report.php" class="text-decoration-none">
            <div class="card text-center h-100 shadow-sm hover-card">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Sales Report</h5>
                    <p class="card-text small text-muted">View sales analytics</p>
                </div>
            </div>
        </a>
    </div>

    <!-- View Sales List (Both) -->
    <div class="col-md-4 mb-3">
        <?php if ($is_admin): ?>
            <a href="admin/sales/list.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-list fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title">Sales List</h5>
                        <p class="card-text small text-muted">View all sales records</p>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <a href="staff/sales/list.php" class="text-decoration-none">
                <div class="card text-center h-100 shadow-sm hover-card">
                    <div class="card-body">
                        <i class="fas fa-list fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title">Sales List</h5>
                        <p class="card-text small text-muted">View your branch sales</p>
                    </div>
                </div>
            </a>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .opacity-50 {
        opacity: 0.5;
    }

    .stats-card {
        border: none;
        border-radius: 10px;
    }
</style>

<?php include 'includes/footer.php'; ?>