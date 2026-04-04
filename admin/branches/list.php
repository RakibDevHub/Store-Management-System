<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

// Get search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query with search
$sql = "SELECT * FROM branches WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (branch_name LIKE '%$search%' 
              OR branch_location LIKE '%$search%' 
              OR phone LIKE '%$search%' 
              OR email LIKE '%$search%')";
}

$sql .= " ORDER BY branch_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<script>
    // Delete confirmation with SweetAlert
    function confirmDelete(branchId, branchName) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete "${branchName}". This will also delete all products and sales in this branch! This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete.php?id=${branchId}`;
            }
        });
        return false;
    }
</script>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Branches Management</h5>
            <a href="add.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i>Add New Branch
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="mb-3">
            <!-- Search Bar -->
            <form method="GET" action="" class="mb-2">
                <div class="row g-2">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control"
                                placeholder="Search by branch name, location, phone, or email..."
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
                </div>
            </form>

            <!-- Search Result Info -->
            <?php if (!empty($search)): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-filter me-1"></i>
                        Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                        (<?php echo $result->num_rows; ?> branch<?php echo $result->num_rows != 1 ? 'es' : ''; ?> found)
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Branch Name</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            // Highlight search term in branch name
                            $branch_name = htmlspecialchars($row['branch_name']);
                            $branch_location = htmlspecialchars($row['branch_location']);

                            if (!empty($search)) {
                                $branch_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $branch_name);
                                $branch_location = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $branch_location);
                            }

                            $js_branch_name = addslashes($row['branch_name']);
                        ?>
                            <tr>
                                <td><?php echo $row['branch_id']; ?></td>
                                <td><strong><?php echo $branch_name; ?></strong></td>
                                <td><?php echo $branch_location ?: '<span class="text-muted">—</span>'; ?></td>
                                <td><?php echo htmlspecialchars($row['phone']) ?: '<span class="text-muted">—</span>'; ?></td>
                                <td><?php echo htmlspecialchars($row['email']) ?: '<span class="text-muted">—</span>'; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit.php?id=<?php echo $row['branch_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($row['branch_id'] != 1): ?>
                                            <button onclick="confirmDelete(<?php echo $row['branch_id']; ?>, '<?php echo $js_branch_name; ?>')" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Cannot delete main branch">
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
                                <i class="fas fa-search fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">
                                    <?php if (!empty($search)): ?>
                                        No branches found matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                    <?php else: ?>
                                        <i class="fas fa-building fa-2x text-muted mb-2 d-block"></i>
                                        No branches found
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search)): ?>
                                    <a href="list.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-eye me-1"></i>View All Branches
                                    </a>
                                <?php else: ?>
                                    <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Add First Branch
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