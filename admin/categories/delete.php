<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Check if category has products
    $check_sql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc();
    
    if ($count['product_count'] == 0) {
        // Get category name for logging
        $sql = "SELECT category_name FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        
        if ($category) {
            logActivity($_SESSION['user_id'], 'Delete Category', "Deleted category: " . $category['category_name']);
            
            // Delete category
            $sql = "DELETE FROM categories WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }
}

redirect('list.php');
?>