<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get categories and branches for dropdowns
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

    // Validation
    if (empty($product_name) || $category_id == 0 || $branch_id == 0 || $price <= 0) {
        $error = "Please fill all required fields";
    } elseif (empty($product_code)) {
        $error = "Product code is required";
    } else {
        // Check if product code already exists
        $check_sql = "SELECT product_id FROM products WHERE product_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $product_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Product code already exists! Please use a unique product code.";
        } else {
            // Insert product
            $sql = "INSERT INTO products (product_name, product_code, category_id, branch_id, quantity, price, purchase_price, reorder_level, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiiiddis", $product_name, $product_code, $category_id, $branch_id, $quantity, $price, $purchase_price, $reorder_level, $description);

            if ($stmt->execute()) {
                // Get the insert ID safely
                $product_id = $conn->insert_id;

                // Log activity
                logActivity($_SESSION['user_id'], 'Add Product', "Added product: $product_name (Code: $product_code) to branch ID: $branch_id");

                $stmt->close();
                $check_stmt->close();

                // Set success message in session
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'text' => "Product '$product_name' has been added successfully."
                ];

                redirect('list.php');
            } else {
                $error = "Database error: " . $stmt->error;
                $stmt->close();
            }
        }
        if (isset($check_stmt)) $check_stmt->close();
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
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
                        <input type="text" name="product_name" class="form-control" value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Product Code <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                        <input type="text" name="product_code" class="form-control" value="<?php echo isset($_POST['product_code']) ? htmlspecialchars($_POST['product_code']) : ''; ?>" required>
                    </div>
                    <small class="text-muted">Unique identifier for this product (e.g., SKU-001)</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Initial Quantity</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                        <input type="number" name="quantity" class="form-control" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : '0'; ?>" min="0">
                    </div>
                    <small class="text-muted">Initial stock quantity for this branch</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Selling Price (BDT) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" required>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Purchase Price (BDT)</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" value="<?php echo isset($_POST['purchase_price']) ? $_POST['purchase_price'] : '0'; ?>">
                    </div>
                    <small class="text-muted">Cost price for profit calculation</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Reorder Level</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-bell"></i></span>
                        <input type="number" name="reorder_level" class="form-control" value="<?php echo isset($_POST['reorder_level']) ? $_POST['reorder_level'] : '5'; ?>" min="0">
                    </div>
                    <small class="text-muted">Alert when stock falls below this level</small>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Product description..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Product
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>