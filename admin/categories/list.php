<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$is_admin = isAdmin();

$sql = "SELECT * FROM categories ORDER BY category_id DESC";
$result = $conn->query($sql);

include '../../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Categories</h2>
        <?php if ($is_admin): ?>
            <a href="add.php" class="btn btn-success">+ Add Category</a>
        <?php endif; ?>
    </div>
    
     <table>
        <thead>
             <tr>
                <th>ID</th>
                <th>Category Name</th>
                <?php if ($is_admin): ?>
                    <th>Actions</th>
                <?php endif; ?>
             </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
             <tr>
                 <td><?php echo $row['category_id']; ?></td>
                 <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <?php if ($is_admin): ?>
                     <td>
                        <a href="edit.php?id=<?php echo $row['category_id']; ?>" class="btn btn-primary" style="padding: 5px 10px;">Edit</a>
                        <a href="delete.php?id=<?php echo $row['category_id']; ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                <?php endif; ?>
             </tr>
            <?php endwhile; ?>
        </tbody>
     </table>
</div>

<?php include '../../includes/footer.php'; ?>