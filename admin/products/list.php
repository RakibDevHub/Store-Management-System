<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get filter parameters
$branch_filter = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';

// Build query with filters
$sql = "SELECT p.*, c.category_name, b.branch_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN branches b ON p.branch_id = b.branch_id
        WHERE 1=1";

// Apply branch filter
if ($branch_filter > 0) {
    $sql .= " AND p.branch_id = $branch_filter";
}

// Apply category filter
if ($category_filter > 0) {
    $sql .= " AND p.category_id = $category_filter";
}

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (p.product_name LIKE '%$search%' 
              OR p.product_code LIKE '%$search%'
              OR c.category_name LIKE '%$search%'
              OR b.branch_name LIKE '%$search%')";
}

// Apply stock filter
if ($stock_filter == 'low') {
    $sql .= " AND p.quantity <= p.reorder_level AND p.quantity > 0";
} elseif ($stock_filter == 'out') {
    $sql .= " AND p.quantity <= 0";
} elseif ($stock_filter == 'in') {
    $sql .= " AND p.quantity > p.reorder_level";
}

$sql .= " ORDER BY p.product_id DESC";
$result = $conn->query($sql);

// Get branches and categories for filters
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

include '../../includes/header.php';
?>

<script>
    // Delete confirmation with SweetAlert
    function confirmDelete(productId, productName) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete "${productName}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete.php?id=${productId}`;
            }
        });
        return false;
    }
</script>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>All Products</h5>
            <a href="add.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i>Add Product
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="mb-3">
            <!-- Search and Filter Form -->
            <form method="GET" action="" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control"
                                placeholder="Search by product name, code, category, or branch..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="branch_id" class="form-select">
                            <option value="0">All Branches</option>
                            <?php
                            $branches->data_seek(0);
                            while ($branch = $branches->fetch_assoc()):
                            ?>
                                <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($branch_filter == $branch['branch_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="category_id" class="form-select">
                            <option value="0">All Categories</option>
                            <?php
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()):
                            ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="stock_filter" class="form-select">
                            <option value="">All Stock Status</option>
                            <option value="in" <?php echo ($stock_filter == 'in') ? 'selected' : ''; ?>In Stock</option>
                            <option value="low" <?php echo ($stock_filter == 'low') ? 'selected' : ''; ?>Low Stock</option>
                            <option value="out" <?php echo ($stock_filter == 'out') ? 'selected' : ''; ?>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <?php if (!empty($search) || $branch_filter > 0 || $category_filter > 0 || !empty($stock_filter)): ?>
                                <a href="list.php" class="btn btn-outline-danger" title="Clear all filters">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Active Filters Display -->
            <?php if (!empty($search) || $branch_filter > 0 || $category_filter > 0 || !empty($stock_filter)): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-filter me-1"></i>
                        Active filters:
                        <?php if (!empty($search)): ?>
                            <span class="badge bg-secondary">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                        <?php endif; ?>
                        <?php if ($branch_filter > 0): ?>
                            <span class="badge bg-secondary">Branch filter applied</span>
                        <?php endif; ?>
                        <?php if ($category_filter > 0): ?>
                            <span class="badge bg-secondary">Category filter applied</span>
                        <?php endif; ?>
                        <?php if ($stock_filter == 'in'): ?>
                            <span class="badge bg-secondary">In Stock only</span>
                        <?php elseif ($stock_filter == 'low'): ?>
                            <span class="badge bg-secondary">Low Stock only</span>
                        <?php elseif ($stock_filter == 'out'): ?>
                            <span class="badge bg-secondary">Out of Stock only</span>
                        <?php endif; ?>
                        (<?php echo $result->num_rows; ?> results found)
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Branch</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Stock Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $display_quantity = max(0, $row['quantity']);
                            $is_negative = $row['quantity'] < 0;

                            if ($row['quantity'] <= 0) {
                                $stock_status = 'Out of Stock';
                                $status_class = 'badge bg-danger';
                            } elseif ($row['quantity'] <= $row['reorder_level']) {
                                $stock_status = 'Low Stock';
                                $status_class = 'badge bg-warning text-dark';
                            } else {
                                $stock_status = 'In Stock';
                                $status_class = 'badge bg-success';
                            }

                            $product_name = htmlspecialchars($row['product_name']);
                            if (!empty($search)) {
                                $product_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $product_name);
                            }

                            $js_product_name = addslashes($row['product_name']);
                        ?>
                            <tr>
                                <td><?php echo $row['product_id']; ?></td>
                                <td>
                                    <strong><?php echo $product_name; ?></strong><br>
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($row['product_code']); ?></small>
                                    <?php if ($is_negative): ?>
                                        <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Invalid quantity!</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name']); ?></span></td>
                                <td><?php echo $display_quantity; ?></td>
                                <td>৳<?php echo number_format($row['price'], 2); ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo $stock_status; ?></span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $row['product_id']; ?>, '<?php echo $js_product_name; ?>')" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">
                                    <?php if (!empty($search) || $branch_filter > 0 || $category_filter > 0 || !empty($stock_filter)): ?>
                                        No products match your filters
                                    <?php else: ?>
                                        No products found
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search) || $branch_filter > 0 || $category_filter > 0 || !empty($stock_filter)): ?>
                                    <a href="list.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-eye me-1"></i>View All Products
                                    </a>
                                <?php else: ?>
                                    <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Add First Product
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>