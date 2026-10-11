<?php
session_start();

$host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "quiz_db";

$conn = new mysqli($host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Database connection failed. Check config.php and MySQL.");
}
$conn->set_charset("utf8mb4");

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: index.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if (($_SESSION["role"] ?? "") !== $role) {
        http_response_code(403);
        exit("Access denied.");
    }
}
