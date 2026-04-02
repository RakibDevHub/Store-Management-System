<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get all categories with product count
$sql = "SELECT c.*, 
        COUNT(p.product_id) as product_count,
        COUNT(DISTINCT p.branch_id) as branch_count
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        GROUP BY c.category_id
        ORDER BY c.category_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Categories Management</h5>
        <a href="add.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus me-1"></i>Add New Category
        </a>
    </div>
    
    <div class="card-body p-0">
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
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['category_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['category_name']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['description'] ?? '—'); ?>
                                <?php if(empty($row['description'])): ?>
                                    <span class="text-muted">No description</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $row['product_count']; ?> Products</span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $row['branch_count']; ?> Branches</span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-primary btn-action" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($row['product_count'] == 0): ?>
                                        <a href="delete.php?id=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Are you sure you want to delete this category?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary btn-action" disabled title="Cannot delete - Category has products">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">No categories found</p>
                                <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i>Create First Category
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