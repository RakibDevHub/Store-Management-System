<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get filter parameters
$branch_filter = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Build query with filters
$sql = "SELECT p.*, c.category_name, b.branch_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN branches b ON p.branch_id = b.branch_id
        WHERE 1=1";

if ($branch_filter > 0) {
    $sql .= " AND p.branch_id = $branch_filter";
}
if ($category_filter > 0) {
    $sql .= " AND p.category_id = $category_filter";
}

$sql .= " ORDER BY p.product_id DESC";
$result = $conn->query($sql);

// Get branches and categories for filters
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>All Products (All Branches)</h5>
        <a href="add.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus me-1"></i>Add Product
        </a>
    </div>
    
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="" class="mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="0">All Branches</option>
                        <?php while($branch = $branches->fetch_assoc()): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($branch_filter == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="0">All Categories</option>
                        <?php while($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="list.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-sync-alt me-1"></i>Reset
                    </a>
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
                            $stock_status = '';
                            $status_class = '';
                            if ($row['quantity'] <= 0) {
                                $stock_status = 'Out of Stock';
                                $status_class = 'badge bg-danger';
                            } elseif ($row['quantity'] <= $row['reorder_level']) {
                                $stock_status = 'Low Stock';
                                $status_class = 'badge bg-warning';
                            } else {
                                $stock_status = 'In Stock';
                                $status_class = 'badge bg-success';
                            }
                        ?>
                        <tr>
                            <td><?php echo $row['product_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br>
                                <small class="text-muted">Code: <?php echo htmlspecialchars($row['product_code']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name']); ?></span>
                            </td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>৳<?php echo number_format($row['price'], 2); ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo $stock_status; ?></span></td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-primary btn-action" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Are you sure you want to delete this product?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-2 d-block"></i>
                                No products found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>