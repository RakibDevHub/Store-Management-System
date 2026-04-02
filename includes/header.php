<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            background: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #2c3e50;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background: #34495e;
        }
        
        .sidebar .nav-link.active {
            background: #3498db;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .stats-card {
            border-radius: 10px;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
        }
        
        footer {
            background: white;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">
            <i class="fas fa-store me-2"></i>Store Management System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-user"></i> Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/branches/list.php">
                                <i class="fas fa-building"></i> Branches
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/categories/list.php">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                    </li>
                    
                    <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/products/list.php">
                                <i class="fas fa-boxes"></i> All Products
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/staff/products/list.php">
                                <i class="fas fa-box"></i> Products
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/sales/add.php">
                            <i class="fas fa-shopping-cart"></i> Record Sale
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/sales/list.php">
                            <i class="fas fa-list"></i> Sales List
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/reports/sales_report.php">
                            <i class="fas fa-chart-line"></i> Sales Report
                        </a>
                    </li>
                    
                    <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/users/list.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto px-md-4 main-content">