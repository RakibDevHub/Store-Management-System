<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isAdmin()) {
    header("Location: ../../index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (user_first_name, user_last_name, user_email, user_password, role) 
                VALUES ('$first_name', '$last_name', '$email', '$hashed_password', '$role')";
        
        if ($conn->query($sql)) {
            header("Location: list.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Add User</h2>
    
    <?php if ($error): ?>
        <div class="alert-danger" style="padding: 10px; margin-bottom: 15px; background: #f8d7da; color: #721c24; border-radius: 3px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Add User</button>
        <a href="list.php" class="btn">Cancel</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>