<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isAdmin()) {
    header("Location: ../../index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT * FROM users ORDER BY user_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Users</h2>
        <a href="add.php" class="btn btn-success">+ Add User</a>
    </div>
    
    <table>
        <thead>
             <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
             </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
             <tr>
                 <td><?php echo $row['user_id']; ?></td>
                 <td><?php echo htmlspecialchars($row['user_first_name'] . ' ' . $row['user_last_name']); ?></td>
                 <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                 <td><?php echo ucfirst($row['role']); ?></td>
                 <td>
                     <a href="delete.php?id=<?php echo $row['user_id']; ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Are you sure?')">Delete</a>
                 </td>
             </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>