<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Get product details for logging
    $sql = "SELECT product_name, branch_id FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $product_name = $product['product_name'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // First, delete related stock movements
            $sql_movements = "DELETE FROM stock_movements WHERE product_id = ?";
            $stmt_movements = $conn->prepare($sql_movements);
            $stmt_movements->bind_param("i", $id);
            $stmt_movements->execute();
            $stmt_movements->close();

            // Update sales table to set product_id to NULL (keep sales history)
            $sql_sales = "UPDATE sales SET product_id = NULL WHERE product_id = ?";
            $stmt_sales = $conn->prepare($sql_sales);
            $stmt_sales->bind_param("i", $id);
            $stmt_sales->execute();
            $stmt_sales->close();

            // Now delete the product
            $sql_product = "DELETE FROM products WHERE product_id = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $id);

            if ($stmt_product->execute()) {
                // Commit transaction
                $conn->commit();

                logActivity($_SESSION['user_id'], 'Delete Product', "Deleted product: " . $product_name);

                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Deleted!',
                    'text' => "Product '$product_name' has been deleted successfully."
                ];
            } else {
                throw new Exception("Failed to delete product");
            }
            $stmt_product->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();

            $_SESSION['flash_message'] = [
                'type' => 'error',
                'title' => 'Error!',
                'text' => "Failed to delete product: " . $e->getMessage()
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error!',
            'text' => "Product not found."
        ];
    }
    $stmt->close();
}

redirect('list.php');
