<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$branch_id = getUserBranch();

if (!isset($_SESSION['staff_cart'])) $_SESSION['staff_cart'] = [];

$products = $conn->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.branch_id = $branch_id AND p.quantity > 0 ORDER BY p.product_name");

if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $sql = "SELECT product_id, product_name, price, quantity as stock FROM products WHERE product_id = ? AND branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $product_id, $branch_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product) {
        if ($quantity > $product['stock']) $_SESSION['cart_error'] = "Only {$product['stock']} units available!";
        else {
            if (isset($_SESSION['staff_cart'][$product_id])) {
                $new_qty = $_SESSION['staff_cart'][$product_id]['quantity'] + $quantity;
                if ($new_qty > $product['stock']) $_SESSION['cart_error'] = "Cannot add. Only {$product['stock']} units available!";
                else $_SESSION['staff_cart'][$product_id]['quantity'] = $new_qty;
            } else $_SESSION['staff_cart'][$product_id] = ['name' => $product['product_name'], 'price' => $product['price'], 'quantity' => $quantity, 'stock' => $product['stock']];
        }
    }
    redirect('add.php');
}

if (isset($_GET['remove'])) {
    unset($_SESSION['staff_cart'][intval($_GET['remove'])]);
    redirect('add.php');
}

if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $pid => $qty) if ($qty <= 0) unset($_SESSION['staff_cart'][$pid]);
    else $_SESSION['staff_cart'][$pid]['quantity'] = intval($qty);
    redirect('add.php');
}

if (isset($_POST['checkout'])) {
    if (empty($_SESSION['staff_cart'])) $_SESSION['cart_error'] = "Cart is empty!";
    else {
        $payment_method = sanitize($_POST['payment_method']);
        $discount = floatval($_POST['discount']);
        $subtotal = 0;
        foreach ($_SESSION['staff_cart'] as $item) $subtotal += $item['price'] * $item['quantity'];
        $tax = $subtotal * 0.05;
        $grand_total = $subtotal + $tax - $discount;
        $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
        $conn->begin_transaction();
        try {
            foreach ($_SESSION['staff_cart'] as $pid => $item) {
                $conn->query("UPDATE products SET quantity = quantity - {$item['quantity']} WHERE product_id = $pid AND branch_id = $branch_id");
                $total_amount = $item['price'] * $item['quantity'];
                $sale_sql = "INSERT INTO sales (invoice_number, product_id, branch_id, staff_id, quantity, unit_price, total_amount, discount, tax, grand_total, payment_method, sale_date, sale_time) VALUES ('$invoice_number', $pid, $branch_id, {$_SESSION['user_id']}, {$item['quantity']}, {$item['price']}, $total_amount, $discount, $tax, $grand_total, '$payment_method', CURDATE(), CURTIME())";
                $conn->query($sale_sql);
            }
            $conn->commit();
            $_SESSION['staff_cart'] = [];
            redirect("invoice.php?invoice=$invoice_number");
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['cart_error'] = "Error: " . $e->getMessage();
            redirect('add.php');
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add Products (<?php echo $_SESSION['branch_name']; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['cart_success'])): ?><div class="alert alert-success"><?php echo $_SESSION['cart_success'];
                                                                                                unset($_SESSION['cart_success']); ?></div><?php endif; ?>
                <?php if (isset($_SESSION['cart_error'])): ?><div class="alert alert-danger"><?php echo $_SESSION['cart_error'];
                                                                                            unset($_SESSION['cart_error']); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3"><label>Search</label><input type="text" id="productSearch" class="form-control"></div>
                    <div class="mb-3"><label>Product</label><select name="product_id" id="productSelect" class="form-select" required>
                            <option value="">Select</option><?php while ($p = $products->fetch_assoc()): ?><option value="<?php echo $p['product_id']; ?>" data-stock="<?php echo $p['quantity']; ?>"><?php echo htmlspecialchars($p['product_name']); ?> (Stock: <?php echo $p['quantity']; ?> | ৳<?php echo number_format($p['price'], 2); ?>)</option><?php endwhile; ?>
                        </select></div>
                    <div class="mb-3"><label>Quantity</label><input type="number" name="quantity" class="form-control" value="1" min="1"></div>
                    <button type="submit" name="add_to_cart" class="btn btn-primary w-100"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Cart</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($_SESSION['staff_cart'])): ?><div class="text-center py-5">
                        <p class="text-muted">Cart empty</p>
                    </div><?php else: ?>
                    <form method="POST">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody><?php $subtotal = 0;
                                    foreach ($_SESSION['staff_cart'] as $pid => $item): $total = $item['price'] * $item['quantity'];
                                        $subtotal += $total; ?><tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>৳<?php echo number_format($item['price'], 2); ?></td>
                                        <td><input type="number" name="quantity[<?php echo $pid; ?>]" value="<?php echo $item['quantity']; ?>" class="form-control form-control-sm" style="width:70px" min="1"></td>
                                        <td>৳<?php echo number_format($total, 2); ?></td>
                                        <td><a href="?remove=<?php echo $pid; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a></td>
                                    </tr><?php endforeach; ?></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                    <td colspan="2">৳<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Tax (5%):</td>
                                    <td colspan="2">৳<?php echo number_format($subtotal * 0.05, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Discount: <input type="number" name="discount" class="form-control d-inline-block" style="width:80px" value="0" step="10"></td>
                                    <td colspan="2">- ৳<span id="discountDisplay">0</span></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end fw-bold h5">Grand Total:</td>
                                    <td colspan="2" class="h5">৳<span id="grandTotal"><?php echo number_format($subtotal * 1.05, 2); ?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6"><select name="payment_method" class="form-select">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="mobile_banking">Mobile Banking</option>
                                    </select></div>
                                <div class="col-md-6 d-flex gap-2"><button type="submit" name="update_cart" class="btn btn-secondary flex-grow-1">Update</button><button type="submit" name="checkout" class="btn btn-success flex-grow-1" onclick="return confirm('Complete sale?')">Checkout</button></div>
                            </div>
                        </div>
                    </form><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('productSearch')?.addEventListener('keyup', function() {
        let f = this.value.toLowerCase(),
            s = document.getElementById('productSelect');
        for (let i = 0; i < s.options.length; i++) s.options[i].style.display = s.options[i].text.toLowerCase().includes(f) ? '' : 'none';
    });
    document.querySelector('input[name="discount"]')?.addEventListener('input', function() {
        let sub = <?php echo $subtotal ?? 0; ?>;
        let grand = sub * 1.05 - (parseFloat(this.value) || 0);
        document.getElementById('discountDisplay').innerText = parseFloat(this.value) || 0;
        document.getElementById('grandTotal').innerText = grand.toFixed(2);
    });
</script>
<?php include '../../includes/footer.php'; ?>