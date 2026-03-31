<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $sql = "DELETE FROM products WHERE product_id = $id";
    $conn->query($sql);
}

header("Location: list.php");
exit();
?>