<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$branch_id = getUserBranch();
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = sanitize($_POST['product_name']);
    $product_code = sanitize($_POST['product_code']);
    $category_id = intval($_POST['category_id']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $purchase_price = floatval($_POST['purchase_price']);
    $reorder_level = intval($_POST['reorder_level']);
    $description = sanitize($_POST['description']);

    if (empty($product_name) || $category_id == 0 || $price <= 0) {
        $error = "Please fill all required fields";
    } elseif (empty($product_code)) {
        $error = "Product code is required";
    } else {
        // Check if product code already exists in this branch
        $check_sql = "SELECT product_id FROM products WHERE product_code = ? AND branch_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $product_code, $branch_id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Product code already exists in your branch!";
        } else {
            $sql = "INSERT INTO products (product_name, product_code, category_id, branch_id, quantity, price, purchase_price, reorder_level, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiiiddis", $product_name, $product_code, $category_id, $branch_id, $quantity, $price, $purchase_price, $reorder_level, $description);

            if ($stmt->execute()) {
                // Log activity
                logActivity($_SESSION['user_id'], 'Add Product', "Added product: $product_name to branch: " . $_SESSION['branch_name']);

                // Set flash message
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'text' => "Product '$product_name' has been added successfully to your branch."
                ];

                redirect('list.php');
            } else {
                $error = "Error: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Product to <?php echo $_SESSION['branch_name']; ?></h5>
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
                        <input type="text" name="product_name" class="form-control" value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required autofocus>
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
                    <label class="form-label">Branch</label>
                    <input type="text" class="form-control" value="<?php echo $_SESSION['branch_name']; ?>" disabled>
                    <small class="text-muted">Products are automatically assigned to your branch</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Initial Quantity</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                        <input type="number" name="quantity" class="form-control" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : '0'; ?>" min="0">
                    </div>
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