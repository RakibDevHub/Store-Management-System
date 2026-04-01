<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$is_admin = isAdmin();
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$branch_name = $_SESSION['branch_name'] ?? 'All Branches';

include 'includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Welcome, <?php echo $user_name; ?>!</h2>
    <p>Role: <strong><?php echo ucfirst($_SESSION['role']); ?></strong></p>
    <?php if(!$is_admin): ?>
        <p>Branch: <strong><?php echo $branch_name; ?></strong></p>
    <?php endif; ?>
</div>

<div class="grid-3">
    <!-- Categories -->
    <?php if($is_admin): ?>
        <a href="admin/categories/list.php" class="menu-item">
            <div style="font-size: 48px;">📁</div>
            <h3>Categories</h3>
            <p>Manage product categories</p>
        </a>
    <?php else: ?>
        <a href="staff/categories/list.php" class="menu-item">
            <div style="font-size: 48px;">📁</div>
            <h3>Categories</h3>
            <p>View categories</p>
        </a>
    <?php endif; ?>
    
    <!-- Products -->
    <?php if($is_admin): ?>
        <a href="admin/products/list.php" class="menu-item">
            <div style="font-size: 48px;">📦</div>
            <h3>Products</h3>
            <p>Manage all products</p>
        </a>
    <?php else: ?>
        <a href="staff/products/list.php" class="menu-item">
            <div style="font-size: 48px;">📦</div>
            <h3>Products</h3>
            <p>Manage branch products</p>
        </a>
    <?php endif; ?>
    
    <!-- Sales -->
    <a href="sales/add.php" class="menu-item">
        <div style="font-size: 48px;">💰</div>
        <h3>Record Sale</h3>
        <p>Sell products</p>
    </a>
    
    <a href="sales/list.php" class="menu-item">
        <div style="font-size: 48px;">📋</div>
        <h3>Sales List</h3>
        <p>View all sales</p>
    </a>
    
    <a href="reports/sales_report.php" class="menu-item">
        <div style="font-size: 48px;">📊</div>
        <h3>Sales Report</h3>
        <p>View reports</p>
    </a>
    
    <?php if($is_admin): ?>
        <!-- Branches (Admin Only) -->
        <a href="admin/branches/list.php" class="menu-item">
            <div style="font-size: 48px;">🏢</div>
            <h3>Branches</h3>
            <p>Manage store branches</p>
        </a>
        
        <!-- Users (Admin Only) -->
        <a href="admin/users/list.php" class="menu-item">
            <div style="font-size: 48px;">👥</div>
            <h3>Users</h3>
            <p>Manage staff users</p>
        </a>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>