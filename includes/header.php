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

    <!-- SweetAlert2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            padding: 12px 20px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background: #34495e;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            padding: 20px;
            min-height: calc(100vh - 56px);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        footer {
            background: white;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }

        /* Popup Flash Message Styles */
        .flash-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .flash-popup-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 90%;
            animation: slideIn 0.3s ease-out;
            overflow: hidden;
        }

        .flash-popup-header {
            padding: 20px 25px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }

        .flash-popup-header i {
            font-size: 64px;
            margin-bottom: 10px;
        }

        .flash-popup-header.success i {
            color: #28a745;
        }

        .flash-popup-header.error i {
            color: #dc3545;
        }

        .flash-popup-header.warning i {
            color: #ffc107;
        }

        .flash-popup-header.info i {
            color: #17a2b8;
        }

        .flash-popup-header h3 {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: 600;
        }

        .flash-popup-body {
            padding: 20px 25px;
            text-align: center;
            color: #6c757d;
            font-size: 16px;
        }

        .flash-popup-footer {
            padding: 15px 25px 25px;
            text-align: center;
        }

        .flash-popup-footer button {
            padding: 10px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .flash-popup-footer button.success {
            background: #28a745;
            color: white;
        }

        .flash-popup-footer button.success:hover {
            background: #218838;
            transform: scale(1.02);
        }

        .flash-popup-footer button.error {
            background: #dc3545;
            color: white;
        }

        .flash-popup-footer button.error:hover {
            background: #c82333;
            transform: scale(1.02);
        }

        .flash-popup-footer button.warning {
            background: #ffc107;
            color: #333;
        }

        .flash-popup-footer button.info {
            background: #17a2b8;
            color: white;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
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
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
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

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/branches/list.php">
                                    <i class="fas fa-building"></i> Branches
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/categories/list.php">
                                    <i class="fas fa-tags"></i> Categories
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="staff/categories/list.php">
                                    <i class="fas fa-tags"></i> Categories
                                </a>
                            </li>
                        <?php endif ?>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/products/list.php">
                                    <i class="fas fa-boxes"></i> Products
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/staff/products/list.php">
                                    <i class="fas fa-box"></i> Products
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/sales/add.php">
                                    <i class="fas fa-shopping-cart"></i> Record Sale
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/sales/list.php">
                                    <i class="fas fa-list"></i> Sales List
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/staff/sales/add.php">
                                    <i class="fas fa-shopping-cart"></i> Record Sale
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/staff/sales/list.php">
                                    <i class="fas fa-list"></i> Sales List
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Reports (Both) -->
                        <li class="nav-item">
                            <a class="nav-link" href="/reports/sales_report.php">
                                <i class="fas fa-chart-line"></i> Sales Report
                            </a>
                        </li>

                        <?php if (isAdmin()): ?>
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

                <!-- Flash Message Popup -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <?php
                    $flash = $_SESSION['flash_message'];
                    $type = $flash['type'];
                    $title = htmlspecialchars($flash['title']);
                    $text = htmlspecialchars($flash['text']);

                    $icon_class = '';
                    $button_class = '';

                    switch ($type) {
                        case 'success':
                            $icon_class = 'success';
                            $button_class = 'success';
                            break;
                        case 'error':
                            $icon_class = 'error';
                            $button_class = 'error';
                            break;
                        case 'warning':
                            $icon_class = 'warning';
                            $button_class = 'warning';
                            break;
                        default:
                            $icon_class = 'info';
                            $button_class = 'info';
                    }
                    ?>
                    <div class="flash-popup-overlay" id="flashPopup">
                        <div class="flash-popup-container">
                            <div class="flash-popup-header <?php echo $icon_class; ?>">
                                <?php if ($type == 'success'): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php elseif ($type == 'error'): ?>
                                    <i class="fas fa-times-circle"></i>
                                <?php elseif ($type == 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle"></i>
                                <?php else: ?>
                                    <i class="fas fa-info-circle"></i>
                                <?php endif; ?>
                                <h3><?php echo $title; ?></h3>
                            </div>
                            <div class="flash-popup-body">
                                <p><?php echo $text; ?></p>
                            </div>
                            <div class="flash-popup-footer">
                                <button class="<?php echo $button_class; ?>" onclick="closeFlashPopup()">
                                    <i class="fas fa-check me-2"></i>OK
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                        function closeFlashPopup() {
                            const popup = document.getElementById('flashPopup');
                            if (popup) {
                                popup.style.animation = 'fadeOut 0.3s ease-out';
                                setTimeout(() => {
                                    popup.remove();
                                }, 300);
                            }
                        }

                        // Close on Escape key
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') {
                                closeFlashPopup();
                            }
                        });

                        // Close when clicking outside the popup
                        document.getElementById('flashPopup')?.addEventListener('click', function(e) {
                            if (e.target === this) {
                                closeFlashPopup();
                            }
                        });

                        // Auto close after 5 seconds
                        setTimeout(function() {
                            closeFlashPopup();
                        }, 5000);
                    </script>

                    <style>
                        @keyframes fadeOut {
                            from {
                                opacity: 1;
                                backdrop-filter: blur(8px);
                            }

                            to {
                                opacity: 0;
                                backdrop-filter: blur(0);
                            }
                        }
                    </style>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>