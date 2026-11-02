<?php
require_once __DIR__ . "/../config/database.php";

$message = "";
$errorType = "error";

$redirect = $_GET['redirect'] ?? '';

if (is_logged_in()) {
    if (is_admin()) {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "index.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please enter both your email address and password.";
    } else {
        $query = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $query->execute([$email]);
        $user = $query->fetch();

        $authenticated = false;
        if ($user) {
            if (password_verify($password, $user["password"])) {
                $authenticated = true;
            } elseif ($password === $user["password"]) {
                
                $authenticated = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateHash = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updateHash->execute([$newHash, $user["user_id"]]);
            }
        }

        if ($authenticated) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["user_type"] = $user["user_type"];

            set_flash('success', "Welcome back, " . $user["name"] . "!");

            if (!empty($redirect) && strpos($redirect, '://') === false) {
                header("Location: " . $redirect);
            } elseif ($user["user_type"] === "Admin") {
                header("Location: " . BASE_URL . "admin/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "index.php");
            }
            exit();
        } else {
            $message = "Invalid email or password. Please try again.";
        }
    }
}

$pageTitle = "Login - Campus Lost & Found";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span class="auth-icon">&#128272;</span>
            <h2>Welcome Back</h2>
            <p>Sign in to Metropolitan University Campus Lost & Found</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger">
                <span>&#9888;</span> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess = get_flash('success')): ?>
            <div class="alert alert-success">
                <span>&#10004;</span> <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="form-vertical">
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="e.g. student@example.com or admin@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        
        <div class="demo-box">
            <h4><span class="badge badge-info">Demo Accounts</span> Quick Logins</h4>
            <div class="demo-grid">
                <div><strong>Admin:</strong> <code>admin@example.com</code> / <code>password123</code></div>
                <div><strong>Student:</strong> <code>student@example.com</code> / <code>password123</code></div>
                <div><strong>Teacher:</strong> <code>rahim@example.com</code> / <code>password123</code></div>
                <div><strong>Staff:</strong> <code>karim@example.com</code> / <code>password123</code></div>
            </div>
        </div>

        <div class="auth-footer">
            Don't have an account yet?
            <a href="register.php">Create Account &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
