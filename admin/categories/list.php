<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isAdmin()) {
    header("Location: ../../index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT * FROM categories ORDER BY category_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Categories</h2>
        <a href="add.php" class="btn btn-success">+ Add Category</a>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['category_id']; ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $row['category_id']; ?>" class="btn btn-primary" style="padding: 5px 10px;">Edit</a>
                    <a href="delete.php?id=<?php echo $row['category_id']; ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>