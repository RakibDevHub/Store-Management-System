<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query with search
$sql = "SELECT c.*, 
        COUNT(p.product_id) as product_count,
        COUNT(DISTINCT p.branch_id) as branch_count
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (c.category_name LIKE '%$search%' 
              OR c.description LIKE '%$search%')";
}

$sql .= " GROUP BY c.category_id
          ORDER BY c.category_id DESC";

$result = $conn->query($sql);

include '../../includes/header.php';
?>

<script>
    // Delete confirmation with SweetAlert
    function confirmDelete(categoryId, categoryName, productCount) {
        let message = `You are about to delete "${categoryName}".`;
        
        if (productCount > 0) {
            message = `Cannot delete "${categoryName}" because it has ${productCount} product(s) associated with it.`;
            Swal.fire({
                title: 'Cannot Delete!',
                text: message,
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete "${categoryName}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete.php?id=${categoryId}`;
            }
        });
        return false;
    }
</script>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Categories Management</h5>
            <a href="add.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i>Add New Category
            </a>
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
                            placeholder="Search by category name or description..."
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

            <!-- Search Result Info -->
            <?php if (!empty($search)): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-filter me-1"></i>
                        Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
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
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Branches</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            // Get values and apply highlighting
                            $category_name = htmlspecialchars($row['category_name']);
                            $description = htmlspecialchars($row['description'] ?? '');

                            if (!empty($search)) {
                                // Highlight search term in category name
                                $category_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $category_name);
                                // Highlight search term in description
                                $description = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $description);
                            }
                            
                            $js_category_name = addslashes($row['category_name']);
                            $product_count = $row['product_count'];
                        ?>
                            <tr>
                                <td><?php echo $row['category_id']; ?></td>
                                <td>
                                    <strong><?php echo $category_name; ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($row['description'])): ?>
                                        <?php echo $description; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $product_count; ?> Products</span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $row['branch_count']; ?> Branches</span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit.php?id=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $row['category_id']; ?>, '<?php echo $js_category_name; ?>', <?php echo $product_count; ?>)" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-search fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">
                                    <?php if (!empty($search)): ?>
                                        No categories found matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                    <?php else: ?>
                                        No categories found
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search)): ?>
                                    <a href="list.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-eye me-1"></i>View All Categories
                                    </a>
                                <?php else: ?>
                                    <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Create First Category
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