<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Staff can only see their branch products
$branch_id = getUserBranch();
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';

// Build query
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.branch_id = $branch_id";

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (p.product_name LIKE '%$search%' 
              OR p.product_code LIKE '%$search%'
              OR c.category_name LIKE '%$search%')";
}

// Apply category filter
if ($category_filter > 0) {
    $sql .= " AND p.category_id = $category_filter";
}

// Apply stock filter
if ($stock_filter == 'low') {
    $sql .= " AND p.quantity <= p.reorder_level AND p.quantity > 0";
} elseif ($stock_filter == 'out') {
    $sql .= " AND p.quantity <= 0";
} elseif ($stock_filter == 'in') {
    $sql .= " AND p.quantity > p.reorder_level";
}

$sql .= " ORDER BY p.product_name";
$result = $conn->query($sql);

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT c.* FROM categories c 
                            INNER JOIN products p ON c.category_id = p.category_id 
                            WHERE p.branch_id = $branch_id 
                            ORDER BY c.category_name");

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Products (<?php echo $_SESSION['branch_name']; ?> Branch)</h5>
            <a href="add.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i>Add Product
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Search and Filter Form -->
        <form method="GET" action="" class="mb-3">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by name, code, or category..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category_id" class="form-select">
                        <option value="0">All Categories</option>
                        <?php
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()):
                        ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category_filter == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="stock_filter" class="form-select">
                        <option value="">All Stock</option>
                        <option value="in" <?php echo ($stock_filter == 'in') ? 'selected' : ''; ?>>In Stock</option>
                        <option value="low" <?php echo ($stock_filter == 'low') ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out" <?php echo ($stock_filter == 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <?php if (!empty($search) || $category_filter > 0 || !empty($stock_filter)): ?>
                            <a href="list.php" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Stock Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $product_name = htmlspecialchars($row['product_name']);
                            if (!empty($search)) {
                                $product_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $product_name);
                            }

                            $stock_status = '';
                            $status_class = '';
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
                        ?>
                            <tr>
                                <td><?php echo $row['product_id']; ?></td>
                                <td>
                                    <strong><?php echo $product_name; ?></strong><br>
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($row['product_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>৳<?php echo number_format($row['price'], 2); ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo $stock_status; ?></span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">No products found in your branch</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i>Add First Product
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>