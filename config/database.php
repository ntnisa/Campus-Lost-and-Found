<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$dbname = "campus_lost_and_found";
$username = "root";
$password = "";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $cleanRoot = preg_replace('#/(auth|user|admin|database|config|includes)$#i', '', $scriptDir);
    define('BASE_URL', rtrim($cleanRoot, '/') . '/');
}

function is_logged_in() {
    return isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
}

function is_admin() {
    return is_logged_in() && isset($_SESSION["user_type"]) && $_SESSION["user_type"] === "Admin";
}

function require_login($redirectTo = null) {
    if (!is_logged_in()) {
        $target = $redirectTo ? "?redirect=" . urlencode($redirectTo) : "";
        header("Location: " . BASE_URL . "auth/login.php" . $target);
        exit();
    }
}

function require_admin() {
    if (!is_admin()) {
        header("Location: " . BASE_URL . "auth/login.php?error=unauthorized");
        exit();
    }
}

function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function set_flash($key, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $message;
}
