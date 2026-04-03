<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$invoice_no = isset($_GET['invoice']) ? sanitize($_GET['invoice']) : '';

if (empty($invoice_no)) {
    redirect('list.php');
}

// Get sale details
$sql = "SELECT s.*, p.product_name, u.first_name, u.last_name, b.branch_name, b.branch_location, b.phone, b.email
        FROM sales s 
        LEFT JOIN products p ON s.product_id = p.product_id 
        LEFT JOIN users u ON s.staff_id = u.user_id
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        WHERE s.invoice_number = '$invoice_no'";

$result = $conn->query($sql);
$sale = $result->fetch_assoc();

if (!$sale) {
    redirect('list.php');
}

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Sales Invoice</h5>
        <div>
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="fas fa-print me-1"></i>Print
            </button>
            <a href="list.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
    <div class="card-body" id="invoiceContent">
        <!-- Invoice Header -->
        <div class="text-center mb-4">
            <h2>🏪 <?php echo htmlspecialchars($sale['branch_name']); ?></h2>
            <p><?php echo htmlspecialchars($sale['branch_location']); ?><br>
                Phone: <?php echo htmlspecialchars($sale['phone']); ?> | Email: <?php echo htmlspecialchars($sale['email']); ?></p>
            <hr>
            <h4>SALES INVOICE</h4>
        </div>

        <!-- Invoice Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="120"><strong>Invoice No:</strong></td>
                        <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td><?php echo date('d F Y', strtotime($sale['sale_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td><?php echo date('h:i A', strtotime($sale['sale_time'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="120"><strong>Sold By:</strong></td>
                        <td><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Payment Method:</strong></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Product Details -->
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Product</th>
                    <th width="100">Quantity</th>
                    <th width="120">Unit Price</th>
                    <th width="120">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                    <td><?php echo $sale['quantity']; ?></td>
                    <td>৳<?php echo number_format($sale['unit_price'], 2); ?></td>
                    <td>৳<?php echo number_format($sale['total_amount'], 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                    <td>৳<?php echo number_format($sale['total_amount'], 2); ?></td>
                </tr>
                <?php if ($sale['discount'] > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Discount:</td>
                        <td>- ৳<?php echo number_format($sale['discount'], 2); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($sale['tax'] > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Tax (5%):</td>
                        <td>+ ৳<?php echo number_format($sale['tax'], 2); ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="table-success">
                    <td colspan="3" class="text-end fw-bold h5">Grand Total:</td>
                    <td class="h5">৳<?php echo number_format($sale['grand_total'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="text-center mt-4">
            <hr>
            <p class="text-muted">Thank you for your business!<br>
                <small>This is a computer generated invoice. No signature required.</small>
            </p>
        </div>
    </div>
</div>

<style>
    @media print {

        .navbar,
        .card-header .btn,
        footer,
        .btn,
        .card-header .d-flex {
            display: none !important;
        }

        .card {
            box-shadow: none !important;
            padding: 0 !important;
        }

        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>

<?php include '../../includes/footer.php'; ?>