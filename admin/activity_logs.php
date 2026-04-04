<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Get filter parameters
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$action_filter = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build main query with JOIN
$sql = "SELECT al.*, u.first_name, u.last_name, u.email, u.role 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        WHERE 1=1";

// Build count query - MUST also have the JOIN for search to work
$count_sql = "SELECT COUNT(*) as total 
              FROM activity_logs al
              LEFT JOIN users u ON al.user_id = u.user_id
              WHERE 1=1";

if ($user_filter > 0) {
    $sql .= " AND al.user_id = $user_filter";
    $count_sql .= " AND al.user_id = $user_filter";
}

if (!empty($action_filter)) {
    $sql .= " AND al.action = '$action_filter'";
    $count_sql .= " AND al.action = '$action_filter'";
}

if (!empty($search)) {
    $sql .= " AND (al.description LIKE '%$search%' 
              OR u.first_name LIKE '%$search%' 
              OR u.last_name LIKE '%$search%'
              OR u.email LIKE '%$search%')";
    $count_sql .= " AND (al.description LIKE '%$search%' 
                   OR u.first_name LIKE '%$search%' 
                   OR u.last_name LIKE '%$search%'
                   OR u.email LIKE '%$search%')";
}

if (!empty($from_date)) {
    $sql .= " AND DATE(al.created_at) >= '$from_date'";
    $count_sql .= " AND DATE(al.created_at) >= '$from_date'";
}

if (!empty($to_date)) {
    $sql .= " AND DATE(al.created_at) <= '$to_date'";
    $count_sql .= " AND DATE(al.created_at) <= '$to_date'";
}

$sql .= " ORDER BY al.created_at DESC LIMIT $offset, $limit";
$result = $conn->query($sql);

// Get total count for pagination
$total_result = $conn->query($count_sql);
$total = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get users for filter dropdown
$users = $conn->query("SELECT user_id, first_name, last_name, email FROM users ORDER BY first_name");

// Get unique actions for filter dropdown
$actions_result = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}

include '../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Activity Logs</h5>
            <div>
                <button onclick="window.location.href='activity_logs.php?export=csv&<?php echo http_build_query($_GET); ?>'" class="btn btn-sm btn-success">
                    <i class="fas fa-download me-1"></i>Export CSV
                </button>
                <button onclick="window.location.href='activity_logs.php'" class="btn btn-sm btn-secondary">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Filter Form -->
        <div class="card bg-light mb-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Logs</h6>
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3">
                        <!-- Search - First position -->
                        <div class="col-md-12">
                            <label class="form-label small">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by user, email, action, or description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <small class="text-muted">Search in user name, email, action type, or description</small>
                        </div>

                        <!-- Filter Row 1 -->
                        <div class="col-md-3">
                            <label class="form-label small">User</label>
                            <select name="user_id" class="form-select">
                                <option value="0">All Users</option>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $user['user_id']; ?>" <?php echo ($user_filter == $user['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Action Type</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo $action; ?>" <?php echo ($action_filter == $action) ? 'selected' : ''; ?>
                                        <?php echo $action; ?>
                                        </option>
                                    <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">From Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-calendar-alt text-muted"></i></span>
                                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">To Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-calendar-alt text-muted"></i></span>
                                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Filter Row 2 - Records per page & Buttons -->
                    <div class="row mt-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Records per page</label>
                            <select name="limit" class="form-select" onchange="this.form.submit()">
                                <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>20 per page</option>
                                <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>50 per page</option>
                                <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>100 per page</option>
                                <option value="200" <?php echo ($limit == 200) ? 'selected' : ''; ?>200 per page</option>
                            </select>
                        </div>
                        <div class="col-md-8 text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Apply Filters
                                </button>
                                <a href="activity_logs.php" class="btn btn-outline-danger">
                                    <i class="fas fa-times me-1"></i>Reset All
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Active Filters Display -->
                <?php if (!empty($search) || $user_filter > 0 || !empty($action_filter) || !empty($from_date) || !empty($to_date)): ?>
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <small class="text-muted fw-bold">
                                <i class="fas fa-filter me-1"></i>Active Filters:
                            </small>
                            <?php if (!empty($search)): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-search me-1"></i>Search: "<?php echo htmlspecialchars($search); ?>"
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['search']);
                                                echo http_build_query($params);
                                                ?>" class="text-white ms-1" style="text-decoration: none;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($user_filter > 0): ?>
                                <?php
                                $user_name = '';
                                $user_query = $conn->query("SELECT first_name, last_name FROM users WHERE user_id = $user_filter");
                                if ($user_query->num_rows > 0) {
                                    $user_data = $user_query->fetch_assoc();
                                    $user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
                                }
                                ?>
                                <span class="badge bg-info">
                                    <i class="fas fa-user me-1"></i>User: <?php echo htmlspecialchars($user_name); ?>
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['user_id']);
                                                echo http_build_query($params);
                                                ?>" class="text-white ms-1" style="text-decoration: none;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($action_filter)): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-tag me-1"></i>Action: <?php echo htmlspecialchars($action_filter); ?>
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['action']);
                                                echo http_build_query($params);
                                                ?>" class="text-dark ms-1" style="text-decoration: none;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($from_date)): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-calendar-alt me-1"></i>From: <?php echo htmlspecialchars($from_date); ?>
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['from_date']);
                                                echo http_build_query($params);
                                                ?>" class="text-white ms-1" style="text-decoration: none;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($to_date)): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-calendar-alt me-1"></i>To: <?php echo htmlspecialchars($to_date); ?>
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['to_date']);
                                                echo http_build_query($params);
                                                ?>" class="text-white ms-1" style="text-decoration: none;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <small class="text-muted ms-auto">
                                <i class="fas fa-database me-1"></i><?php echo $total; ?> results found
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $action_badge = '';
                            $action_icon = '';
                            switch ($row['action']) {
                                case 'Login':
                                    $action_badge = 'bg-success';
                                    $action_icon = 'fa-sign-in-alt';
                                    break;
                                case 'Logout':
                                    $action_badge = 'bg-secondary';
                                    $action_icon = 'fa-sign-out-alt';
                                    break;
                                case 'Add Category':
                                case 'Add Branch':
                                case 'Add Product':
                                case 'Add User':
                                    $action_badge = 'bg-primary';
                                    $action_icon = 'fa-plus-circle';
                                    break;
                                case 'Edit Category':
                                case 'Edit Branch':
                                case 'Edit Product':
                                case 'Edit User':
                                    $action_badge = 'bg-warning text-dark';
                                    $action_icon = 'fa-edit';
                                    break;
                                case 'Delete Category':
                                case 'Delete Branch':
                                case 'Delete Product':
                                case 'Delete User':
                                    $action_badge = 'bg-danger';
                                    $action_icon = 'fa-trash-alt';
                                    break;
                                case 'Record Sale':
                                    $action_badge = 'bg-info';
                                    $action_icon = 'fa-shopping-cart';
                                    break;
                                default:
                                    $action_badge = 'bg-secondary';
                                    $action_icon = 'fa-clock';
                            }
                        ?>
                            <tr>
                                <td><?php echo date('d M Y h:i:s A', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                <td><span class="badge bg-<?php echo ($row['role'] == 'admin') ? 'danger' : 'primary'; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                <td>
                                    <span class="badge <?php echo $action_badge; ?>">
                                        <i class="fas <?php echo $action_icon; ?> me-1"></i>
                                        <?php echo htmlspecialchars($row['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><code><?php echo htmlspecialchars($row['ip_address']); ?></code></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-history fa-4x text-muted mb-3 d-block"></i>
                                <h6 class="text-muted">No activity logs found</h6>
                                <p class="text-muted small">Try adjusting your filters</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&user_id=<?php echo $user_filter; ?>&action=<?php echo urlencode($action_filter); ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&limit=<?php echo $limit; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1&user_id=' . $user_filter . '&action=' . urlencode($action_filter) . '&search=' . urlencode($search) . '&from_date=' . $from_date . '&to_date=' . $to_date . '&limit=' . $limit . '">1</a></li>';
                        if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&user_id=<?php echo $user_filter; ?>&action=<?php echo urlencode($action_filter); ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&limit=<?php echo $limit; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&user_id=' . $user_filter . '&action=' . urlencode($action_filter) . '&search=' . urlencode($search) . '&from_date=' . $from_date . '&to_date=' . $to_date . '&limit=' . $limit . '">' . $total_pages . '</a></li>';
                    }
                    ?>

                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&user_id=<?php echo $user_filter; ?>&action=<?php echo urlencode($action_filter); ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&limit=<?php echo $limit; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Export CSV Function -->
<?php
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date/Time', 'User', 'Email', 'Role', 'Action', 'Description', 'IP Address']);

    $export_sql = "SELECT al.*, u.first_name, u.last_name, u.email, u.role 
                   FROM activity_logs al
                   LEFT JOIN users u ON al.user_id = u.user_id
                   WHERE 1=1";

    if ($user_filter > 0) $export_sql .= " AND al.user_id = $user_filter";
    if (!empty($action_filter)) $export_sql .= " AND al.action = '$action_filter'";
    if (!empty($search)) $export_sql .= " AND (al.description LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')";
    if (!empty($from_date)) $export_sql .= " AND DATE(al.created_at) >= '$from_date'";
    if (!empty($to_date)) $export_sql .= " AND DATE(al.created_at) <= '$to_date'";

    $export_sql .= " ORDER BY al.created_at DESC";
    $export_result = $conn->query($export_sql);

    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($row['created_at'])),
            $row['first_name'] . ' ' . $row['last_name'],
            $row['email'],
            $row['role'],
            $row['action'],
            $row['description'],
            $row['ip_address']
        ]);
    }

    fclose($output);
    exit();
}
?>

<?php include '../includes/footer.php'; ?>