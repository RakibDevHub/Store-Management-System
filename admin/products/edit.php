<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    redirect('list.php');
}

// Get product details
$sql = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    redirect('list.php');
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = sanitize($_POST['product_name']);
    $product_code = sanitize($_POST['product_code']);
    $category_id = intval($_POST['category_id']);
    $branch_id = intval($_POST['branch_id']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $purchase_price = floatval($_POST['purchase_price']);
    $reorder_level = intval($_POST['reorder_level']);
    $description = sanitize($_POST['description']);

    if (empty($product_name) || $category_id == 0 || $branch_id == 0 || $price <= 0) {
        $error = "Please fill all required fields";
    } else {
        // Check if product code exists for other products
        $check_sql = "SELECT product_id FROM products WHERE product_code = ? AND product_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $product_code, $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Product code already exists!";
        } else {
            // Track quantity change for stock movement
            $quantity_diff = $quantity - $product['quantity'];

            $sql = "UPDATE products SET 
                    product_name = ?, product_code = ?, category_id = ?, branch_id = ?, 
                    quantity = ?, price = ?, purchase_price = ?, reorder_level = ?, description = ? 
                    WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssiiiddisi",
                $product_name,
                $product_code,
                $category_id,
                $branch_id,
                $quantity,
                $price,
                $purchase_price,
                $reorder_level,
                $description,
                $id
            );

            if ($stmt->execute()) {
                // Log activity
                logActivity($_SESSION['user_id'], 'Edit Product', "Edited product: $product_name");

                // Set success message in session
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'text' => "Product '$product_name' has been updated successfully."
                ];

                redirect('list.php');
            } else {
                $error = "Error: " . $conn->error;
            }
            $check_stmt->close();
        }
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Product</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Product Code <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                        <input type="text" name="product_code" class="form-control" value="<?php echo htmlspecialchars($product['product_code']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch</option>
                        <?php while ($branch = $branches->fetch_assoc()): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($branch['branch_id'] == $product['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Quantity</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                        <input type="number" name="quantity" class="form-control" value="<?php echo $product['quantity']; ?>" min="0">
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Price (BDT) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $product['price']; ?>" min="0" required>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Purchase Price</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" name="purchase_price" class="form-control" step="0.01" value="<?php echo $product['purchase_price']; ?>" min="0">
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Reorder Level</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-bell"></i></span>
                        <input type="number" name="reorder_level" class="form-control" value="<?php echo $product['reorder_level']; ?>" min="0">
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Product
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>