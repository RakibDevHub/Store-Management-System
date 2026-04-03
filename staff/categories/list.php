<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Staff can view, but not edit/delete categories
// Only show categories that have products in their branch

$branch_id = getUserBranch();
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query - only show categories with products in this branch
$sql = "SELECT DISTINCT c.*, 
        COUNT(p.product_id) as product_count
        FROM categories c
        INNER JOIN products p ON c.category_id = p.category_id
        WHERE p.branch_id = $branch_id";

if (!empty($search)) {
    $sql .= " AND (c.category_name LIKE '%$search%' 
              OR c.description LIKE '%$search%')";
}

$sql .= " GROUP BY c.category_id
          ORDER BY c.category_name";

$result = $conn->query($sql);

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Categories (<?php echo $_SESSION['branch_name']; ?> Branch)</h5>
        </div>
    </div>

    <div class="card-body">
        <!-- Search Bar -->
        <div class="mb-3">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search categories..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <?php if (!empty($search)): ?>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                </div>
            </form>

            <?php if (!empty($search)): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-filter me-1"></i>
                        Found <?php echo $result->num_rows; ?> category(es)
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products in Branch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $category_name = htmlspecialchars($row['category_name']);
                            $description = htmlspecialchars($row['description'] ?? '');

                            if (!empty($search)) {
                                $category_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $category_name);
                                $description = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $description);
                            }
                        ?>
                            <tr>
                                <td><?php echo $row['category_id']; ?></td>
                                <td><strong><?php echo $category_name; ?></strong></td>
                                <td><?php echo $description ?: '<span class="text-muted">—</span>'; ?></td>
                                <td><span class="badge bg-primary"><?php echo $row['product_count']; ?> Products</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">
                                    <?php if (!empty($search)): ?>
                                        No categories found matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                    <?php else: ?>
                                        No categories available in your branch
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3 alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <small>Categories are managed by Admin. You can only view categories that have products in your branch.</small>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>