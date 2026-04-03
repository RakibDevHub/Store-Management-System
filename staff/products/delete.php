<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$branch_id = getUserBranch();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Get product details - ensure it belongs to staff's branch
    $sql = "SELECT product_name FROM products WHERE product_id = ? AND branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $branch_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        logActivity($_SESSION['user_id'], 'Delete Product', "Deleted product: " . $product['product_name']);
        
        // Delete product
        $sql = "DELETE FROM products WHERE product_id = ? AND branch_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $branch_id);
        $stmt->execute();
    }
}

redirect('list.php');
?>