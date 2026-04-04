<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$selected_branch = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

// Get all branches
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Get products for selected branch
$products = null;
if ($selected_branch > 0) {
    $products = $conn->query("SELECT p.*, c.category_name 
                              FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.category_id 
                              WHERE p.branch_id = $selected_branch AND p.quantity > 0 
                              ORDER BY p.product_name");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch_id = intval($_POST['branch_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $payment_method = sanitize($_POST['payment_method']);
    $discount = floatval($_POST['discount']);

    // Get product details
    $product_query = $conn->query("SELECT product_name, price, quantity as stock FROM products WHERE product_id = $product_id AND branch_id = $branch_id");
    $product = $product_query->fetch_assoc();

    if (!$product) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Error!',
            'text' => 'Product not found!'
        ];
    } elseif ($quantity > $product['stock']) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Stock Error!',
            'text' => "Only {$product['stock']} units available!"
        ];
    } elseif ($quantity <= 0) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'title' => 'Invalid Quantity!',
            'text' => 'Quantity must be greater than 0'
        ];
    } else {
        $total_amount = $product['price'] * $quantity;
        $tax = $total_amount * 0.05;
        $grand_total = $total_amount + $tax - $discount;
        $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update product stock
            $new_stock = $product['stock'] - $quantity;
            $conn->query("UPDATE products SET quantity = $new_stock WHERE product_id = $product_id AND branch_id = $branch_id");

            // Insert sale record
            $sale_sql = "INSERT INTO sales (invoice_number, product_id, branch_id, staff_id, quantity, unit_price, total_amount, discount, tax, grand_total, payment_method, sale_date, sale_time) 
                         VALUES ('$invoice_number', $product_id, $branch_id, {$_SESSION['user_id']}, $quantity, {$product['price']}, $total_amount, $discount, $tax, $grand_total, '$payment_method', CURDATE(), CURTIME())";
            $conn->query($sale_sql);

            $conn->commit();
            
            // Log activity
            logActivity($_SESSION['user_id'], 'Record Sale', "Recorded sale of {$quantity} x {$product['product_name']} (Invoice: $invoice_number) for branch ID: $branch_id");
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'title' => 'Success!',
                'text' => "Sale recorded successfully! Invoice: $invoice_number"
            ];

            // Clear form after successful submission
            $selected_branch = 0;
            $products = null;
            
            redirect('list.php');

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'title' => 'Error!',
                'text' => "Error processing sale: " . $e->getMessage()
            ];
        }
    }
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Record Sale (Admin)</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" id="branch_id" class="form-select" required>
                        <option value="">-- Select Branch --</option>
                        <?php while ($branch = $branches->fetch_assoc()): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($selected_branch == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Product <span class="text-danger">*</span></label>
                    <select name="product_id" id="product_id" class="form-select" <?php echo !$products ? 'disabled' : ''; ?> required>
                        <option value="">-- Select Product --</option>
                        <?php if ($products && $products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?> (Stock: <?php echo $product['quantity']; ?> | ৳<?php echo number_format($product['price'], 2); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php elseif ($selected_branch > 0): ?>
                            <option value="">No products available in this branch</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Discount (BDT)</label>
                    <input type="number" name="discount" id="discount" class="form-control" value="0" step="10" min="0">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile_banking">Mobile Banking</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Total Amount</label>
                    <input type="text" id="total_amount" class="form-control" readonly>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Record Sale
            </button>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-list me-1"></i>View Sales
            </a>
        </form>
    </div>
</div>

<script>
    // Auto-submit when branch changes
    document.getElementById('branch_id').addEventListener('change', function() {
        let branch_id = this.value;
        if (branch_id) {
            window.location.href = 'add.php?branch_id=' + branch_id;
        }
    });

    // Calculate total
    document.getElementById('product_id').addEventListener('change', calculateTotal);
    document.getElementById('quantity').addEventListener('input', calculateTotal);
    document.getElementById('discount').addEventListener('input', calculateTotal);

    function calculateTotal() {
        let productSelect = document.getElementById('product_id');
        let selectedOption = productSelect.options[productSelect.selectedIndex];
        let price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        let quantity = parseInt(document.getElementById('quantity').value) || 0;
        let discount = parseFloat(document.getElementById('discount').value) || 0;

        let subtotal = price * quantity;
        let tax = subtotal * 0.05;
        let grandTotal = subtotal + tax - discount;

        document.getElementById('total_amount').value = '৳' + grandTotal.toFixed(2);
    }
</script>

<?php include '../../includes/footer.php'; ?>