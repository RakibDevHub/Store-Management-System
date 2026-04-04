<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Prevent deleting main branch (ID = 1)
if ($id > 0 && $id != 1) {
    // Get branch name for logging
    $sql = "SELECT branch_name FROM branches WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $branch = $stmt->get_result()->fetch_assoc();
    $branch_name = $branch['branch_name'] ?? 'Unknown';

    // Start transaction
    $conn->begin_transaction();

    try {
        // First, update products in this branch to set branch_id to NULL or delete?
        // Option 1: Delete products in this branch
        $sql_products = "DELETE FROM products WHERE branch_id = ?";
        $stmt_products = $conn->prepare($sql_products);
        $stmt_products->bind_param("i", $id);
        $stmt_products->execute();
        $stmt_products->close();

        // Update sales in this branch to set branch_id to NULL
        $sql_sales = "UPDATE sales SET branch_id = NULL WHERE branch_id = ?";
        $stmt_sales = $conn->prepare($sql_sales);
        $stmt_sales->bind_param("i", $id);
        $stmt_sales->execute();
        $stmt_sales->close();

        // Update users in this branch to set branch_id to NULL
        $sql_users = "UPDATE users SET branch_id = NULL WHERE branch_id = ?";
        $stmt_users = $conn->prepare($sql_users);
        $stmt_users->bind_param("i", $id);
        $stmt_users->execute();
        $stmt_users->close();

        // Delete the branch
        $sql_branch = "DELETE FROM branches WHERE branch_id = ?";
        $stmt_branch = $conn->prepare($sql_branch);
        $stmt_branch->bind_param("i", $id);
        $stmt_branch->execute();
        $stmt_branch->close();

        $conn->commit();

        // Log activity
        logActivity($_SESSION['user_id'], 'Delete Branch', "Deleted branch: $branch_name");

        // Set flash message
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'title' => 'Deleted!',
            'text' => "Branch '$branch_name' has been deleted successfully."
        ];
    } catch (Exception $e) {
        $conn->rollback();

        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error!',
            'text' => "Failed to delete branch: " . $e->getMessage()
        ];
    }
} else {
    $_SESSION['flash_message'] = [
        'type' => 'warning',
        'title' => 'Cannot Delete!',
        'text' => "The main branch cannot be deleted."
    ];
}

redirect('list.php');
