<?php

// Get database connection
// function getDB() {
//     require_once __DIR__ . '/../config/database.php';
//     global $conn;
//     return $conn;
// }

// Sanitize input data
function sanitize($data)
{
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Display alert message
function showMessage($message, $type = 'success')
{
    $icon = ($type == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle';
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            <i class='fas $icon me-2'></i>$message
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
// Redirect to another page
function redirect($url)
{
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Get user's branch ID
function getUserBranch()
{
    return isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
}

// Log activity
function logActivity($user_id, $action, $description)
{
    require_once __DIR__ . '/../config/database.php';
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    if ($conn) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $action, $description, $ip, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Get categories for dropdown
function getCategories()
{
    global $conn;
    $result = $conn->query("SELECT * FROM categories ORDER BY category_name");
    return $result;
}

// Get branches for dropdown
function getBranches()
{
    global $conn;
    $result = $conn->query("SELECT * FROM branches ORDER BY branch_name");
    return $result;
}

// Get products for a specific branch (for staff)
function getBranchProducts($branch_id)
{
    global $conn;
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.branch_id = ?
            ORDER BY p.product_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get categories that have products in a specific branch
function getBranchCategories($branch_id)
{
    global $conn;
    $sql = "SELECT DISTINCT c.* 
            FROM categories c
            INNER JOIN products p ON p.category_id = c.category_id
            WHERE p.branch_id = ?
            ORDER BY c.category_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    return $stmt->get_result();
}
