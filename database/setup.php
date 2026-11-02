<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "campus_lost_and_found";

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup - Campus Lost & Found</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; padding: 40px; color: #1e293b; }
        .box { max-width: 650px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        h1 { color: #16325c; margin-top: 0; font-size: 24px; }
        .success { color: #047857; background: #d1fae5; padding: 12px; border-radius: 6px; margin: 15px 0; font-weight: 500; }
        .error { color: #b91c1c; background: #fee2e2; padding: 12px; border-radius: 6px; margin: 15px 0; }
        .info { background: #e0f2fe; color: #0369a1; padding: 12px; border-radius: 6px; margin: 15px 0; }
        .btn { display: inline-block; background: #16325c; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; }
        .btn:hover { background: #16a6a1; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; color: #475569; }
    </style>
</head>
<body>
<div class="box">
    <h1>Database Setup & Migration</h1>
<?php
try {
    
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found at " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    
    $sampleHash = password_hash('password123', PASSWORD_DEFAULT);
    
    $sql = preg_replace('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $sampleHash, $sql);
    
    
    $pdo->exec($sql);

    
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE password = 'password123' OR email IN ('admin@example.com', 'student@example.com', 'tanvir@example.com', 'rahim@example.com', 'karim@example.com')");
    $updateStmt->execute([$sampleHash]);
    
    echo '<div class="success">Database <strong>' . htmlspecialchars($dbname) . '</strong> and all tables have been successfully installed and seeded!</div>';
    
    echo '<div class="info">Default credentials (all passwords are: <code>password123</code>):</div>';
    echo '<table>';
    echo '<tr><th>Role</th><th>Email</th><th>Password</th><th>Default User</th></tr>';
    echo '<tr><td><strong>Admin</strong></td><td>admin@example.com</td><td>password123</td><td>Administrator</td></tr>';
    echo '<tr><td><strong>Student</strong></td><td>student@example.com</td><td>password123</td><td>Student User</td></tr>';
    echo '<tr><td><strong>Teacher</strong></td><td>rahim@example.com</td><td>password123</td><td>Rahim Ahmed</td></tr>';
    echo '<tr><td><strong>Staff</strong></td><td>karim@example.com</td><td>password123</td><td>Karim Ullah</td></tr>';
    echo '</table>';
    
    echo '<p><a class="btn" href="../index.php">Go to Application &rarr;</a></p>';
    
} catch (Exception $e) {
    echo '<div class="error">Setup Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
</div>
</body>
</html>
