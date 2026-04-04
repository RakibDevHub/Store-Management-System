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
            $category_name = $category['category_name'];
            
            // Log activity
            logActivity($_SESSION['user_id'], 'Delete Category', "Deleted category: " . $category_name);
            
            // Delete category
            $sql = "DELETE FROM categories WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Set flash message
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Deleted!',
                    'text' => "Category '$category_name' has been deleted successfully."
                ];
            } else {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'title' => 'Error!',
                    'text' => "Failed to delete category."
                ];
            }
            $stmt->close();
        }
    } else {
        // Category has products - cannot delete
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'title' => 'Cannot Delete!',
            'text' => "This category has {$count['product_count']} product(s) associated with it. Please reassign or delete those products first."
        ];
    }
    $check_stmt->close();
}

redirect('list.php');
?>