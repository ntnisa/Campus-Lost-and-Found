<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : "Campus Lost & Found - Metropolitan University"; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>

    
    <nav class="navbar">
        <div class="container nav-container">
            <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo">
                <span class="brand-icon">&#128269;</span>
                <span class="brand-text">
                    Campus Lost &amp; Found
                    <small>Metropolitan University</small>
                </span>
            </a>

            <button class="mobile-toggle" id="mobileMenuBtn" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>

            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li>
                        <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo ($currentPage === 'index.php' && $currentDir !== 'auth' && $currentDir !== 'user' && $currentDir !== 'admin') ? 'active' : ''; ?>">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>search.php" class="<?php echo ($currentPage === 'search.php') ? 'active' : ''; ?>">
                            Search Items
                        </a>
                    </li>

                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-highlight <?php echo ($currentDir === 'admin') ? 'active' : ''; ?>">
                                    <span class="nav-icon">&#9881;</span> Admin Dashboard
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/report-lost.php" class="<?php echo ($currentPage === 'report-lost.php') ? 'active' : ''; ?>">
                                    <span class="nav-icon">&#10060;</span> Report Lost
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/report-found.php" class="<?php echo ($currentPage === 'report-found.php') ? 'active' : ''; ?>">
                                    <span class="nav-icon">&#10004;</span> Report Found
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/submit-claim.php" class="<?php echo ($currentPage === 'submit-claim.php') ? 'active' : ''; ?>">
                                    Claim Item
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>user/my-reports.php" class="<?php echo ($currentPage === 'my-reports.php') ? 'active' : ''; ?>">
                                    My Activity
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <div class="nav-auth">
                    <?php if (is_logged_in()): ?>
                        <div class="user-pill">
                            <span class="role-badge role-<?php echo strtolower($_SESSION['user_type'] ?? 'student'); ?>">
                                <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'User'); ?>
                            </span>
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-sm btn-outline">Login</a>
                        <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-sm btn-accent">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    
    <?php if ($globalSuccess = get_flash('success')): ?>
        <div class="flash-toast flash-toast-success">
            <div class="container toast-content">
                <span>&#10004; <?php echo htmlspecialchars($globalSuccess); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($globalError = get_flash('error')): ?>
        <div class="flash-toast flash-toast-danger">
            <div class="container toast-content">
                <span>&#9888; <?php echo htmlspecialchars($globalError); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <main class="main-content">

