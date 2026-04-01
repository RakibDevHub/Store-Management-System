<?php
session_start();

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    require_once 'includes/functions.php';
    require_once 'config/database.php';

    // Create connection for logging
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $_SESSION['user_id'], $action, $description, $ip, $user_agent);
    $action = 'Logout';
    $description = 'User logged out';
    $stmt->execute();
    $stmt->close();
}

session_destroy();
header("Location: login.php");
exit();
