<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    redirect('../../index.php');
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query with search
$sql = "SELECT u.*, b.branch_name 
        FROM users u 
        LEFT JOIN branches b ON u.branch_id = b.branch_id 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (u.first_name LIKE '%$search%' 
              OR u.last_name LIKE '%$search%' 
              OR u.email LIKE '%$search%'
              OR u.role LIKE '%$search%'
              OR b.branch_name LIKE '%$search%')";
}

$sql .= " ORDER BY u.user_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<script>
    // Delete confirmation with SweetAlert
    function confirmDelete(userId, userName, userRole) {
        if (userRole === 'admin') {
            Swal.fire({
                title: 'Cannot Delete!',
                text: `Admin user "${userName}" cannot be deleted.`,
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return false;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete user "${userName}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete.php?id=${userId}`;
            }
        });
        return false;
    }
</script>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Users Management</h5>
            <a href="add.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i>Add New User
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
                            placeholder="Search by name, email, role, or branch..."
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
                        (<?php echo $result->num_rows; ?> users found)
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            // Highlight search term
                            $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                            $email = htmlspecialchars($row['email']);

                            if (!empty($search)) {
                                $full_name = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $full_name);
                                $email = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $email);
                            }

                            $status_badge = ($row['is_active'] == 1) ? 'bg-success' : 'bg-secondary';
                            $status_text = ($row['is_active'] == 1) ? 'Active' : 'Inactive';
                        ?>
                            <tr>
                                <td><?php echo $row['user_id']; ?></td>
                                <td><strong><?php echo $full_name; ?></strong></td>
                                <td><?php echo $email; ?></td>
                                <td><?php echo htmlspecialchars($row['phone']) ?: '<span class="text-muted">—</span>'; ?></td>
                                <td>
                                    <?php
                                    $role_badge = ($row['role'] == 'admin') ? 'bg-danger' : 'bg-primary';
                                    ?>
                                    <span class="badge <?php echo $role_badge; ?>"><?php echo ucfirst($row['role']); ?></span>
                                </td>
                                <td>
                                    <?php if ($row['branch_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($row['branch_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></td>
                                <td><?php echo $row['last_login'] ? date('d M Y', strtotime($row['last_login'])) : '<span class="text-muted">Never</span>'; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($row['user_id'] != 1 && $row['user_id'] != $_SESSION['user_id']): ?>
                                            <button onclick="confirmDelete(<?php echo $row['user_id']; ?>, '<?php echo addslashes($row['first_name'] . ' ' . $row['last_name']); ?>', '<?php echo $row['role']; ?>')" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Cannot delete this user">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-search fa-3x text-muted mb-2 d-block"></i>
                                <p class="mb-0">
                                    <?php if (!empty($search)): ?>
                                        No users found matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                    <?php else: ?>
                                        <i class="fas fa-users fa-2x text-muted mb-2 d-block"></i>
                                        No users found
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search)): ?>
                                    <a href="list.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-eye me-1"></i>View All Users
                                    </a>
                                <?php else: ?>
                                    <a href="add.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Add First User
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