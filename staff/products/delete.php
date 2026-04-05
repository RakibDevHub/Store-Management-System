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
        $product_name = $product['product_name'];

        // Log activity
        logActivity($_SESSION['user_id'], 'Delete Product', "Deleted product: " . $product_name);

        // Delete product
        $sql = "DELETE FROM products WHERE product_id = ? AND branch_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $branch_id);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'title' => 'Deleted!',
                'text' => "Product '$product_name' has been deleted successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'title' => 'Error!',
                'text' => "Failed to delete product."
            ];
        }
        $stmt->close();
    }
}

redirect('list.php');
