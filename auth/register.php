<?php
require_once __DIR__ . "/../config/database.php";

$message = "";
$messageType = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $userType = $_POST["user_type"] ?? "Student";

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid university or personal email address.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match. Please verify your password.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif (!in_array($userType, ["Student", "Teacher", "Staff"], true)) {
        $message = "Please choose a valid campus role.";
    } else {
        
        $checkQuery = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkQuery->execute([$email]);

        if ($checkQuery->fetch()) {
            $message = "This email address is already registered. Please login instead.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertQuery = $conn->prepare("
                INSERT INTO users (name, email, phone, password, user_type)
                VALUES (?, ?, ?, ?, ?)
            ");

            if ($insertQuery->execute([$name, $email, $phone, $hashedPassword, $userType])) {
                set_flash('success', "Registration successful! You can now log in with your email.");
                header("Location: login.php");
                exit();
            } else {
                $message = "Failed to register. Please try again.";
            }
        }
    }
}

$pageTitle = "Register - Campus Lost & Found";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span class="auth-icon">&#128101;</span>
            <h2>Create Your Account</h2>
            <p>Join Metropolitan University Campus Lost & Found Portal</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <span>&#9888;</span> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="form-vertical">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="e.g. Sarah Ahmed"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Campus / Personal Email <span class="required">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="e.g. student@metrouni.edu.bd"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone / Mobile Number <span class="required">*</span></label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="e.g. 017xxxxxxxx"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="user_type">Campus Role / Account Type <span class="required">*</span></label>
                <select id="user_type" name="user_type" required>
                    <option value="Student" <?php echo (($_POST['user_type'] ?? '') === 'Student') ? 'selected' : ''; ?>>Student</option>
                    <option value="Teacher" <?php echo (($_POST['user_type'] ?? '') === 'Teacher') ? 'selected' : ''; ?>>Teacher / Faculty</option>
                    <option value="Staff" <?php echo (($_POST['user_type'] ?? '') === 'Staff') ? 'selected' : ''; ?>>Staff / University Employee</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group col-half">
                    <label for="password">Password <span class="required">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 6 characters"
                        required
                    >
                </div>
                <div class="form-group col-half">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-type password"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Complete Registration</button>
        </form>

        <div class="auth-footer">
            Already have an account?
            <a href="login.php">Login here &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
