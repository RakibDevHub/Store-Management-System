<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$user_name = $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'];
$is_admin = isAdmin();

include 'includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Welcome, <?php echo $user_name; ?>!</h2>
    <p>Role: <?php echo ucfirst($_SESSION['role']); ?></p>
</div>

<div class="menu">
    <!-- Categories -->
    <a href="admin/categories/list.php" class="menu-item">
        <div style="font-size: 48px;">📁</div>
        <h3>Categories</h3>
        <p>Manage product categories</p>
    </a>

    <!-- Products -->
    <a href="admin/products/list.php" class="menu-item">
        <div style="font-size: 48px;">📦</div>
        <h3>Products</h3>
        <p>Manage products</p>
    </a>

    <!-- Sales -->
    <a href="sales/add.php" class="menu-item">
        <div style="font-size: 48px;">💰</div>
        <h3>Record Sale</h3>
        <p>Sell products</p>
    </a>

    <!-- Sales List -->
    <a href="sales/list.php" class="menu-item">
        <div style="font-size: 48px;">📋</div>
        <h3>Sales List</h3>
        <p>View all sales</p>
    </a>

    <!-- Sales Report -->
    <a href="reports/sales_report.php" class="menu-item">
        <div style="font-size: 48px;">📊</div>
        <h3>Sales Report</h3>
        <p>View reports</p>
    </a>

    <?php if ($is_admin): ?>
        <!-- Users (Admin Only) -->
        <a href="admin/users/list.php" class="menu-item">
            <div style="font-size: 48px;">👥</div>
            <h3>Users</h3>
            <p>Manage users</p>
        </a>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>