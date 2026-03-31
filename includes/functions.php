<?php

function sanitize($data)
{
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

function getCategories()
{
    global $conn;
    $result = $conn->query("SELECT * FROM categories ORDER BY category_name");
    return $result;
}

function getProducts()
{
    global $conn;
    $result = $conn->query("SELECT p.*, c.category_name FROM products p 
                            LEFT JOIN categories c ON p.product_category = c.category_id 
                            ORDER BY p.product_id DESC");
    return $result;
}

function showMessage($message, $type = 'success')
{
    $class = ($type == 'success') ? 'alert-success' : 'alert-danger';
    echo "<div class='$class' style='padding: 10px; margin-bottom: 15px; border-radius: 5px;'>$message</div>";
}

function redirect($url)
{
    header("Location: $url");
    exit();
}
