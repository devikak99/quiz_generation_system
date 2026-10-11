<?php
require "config.php";
if (isset($_SESSION["user_id"])) {
    header("Location: " . ($_SESSION["role"] === "teacher" ? "teacher_dashboard.php" : "student_dashboard.php"));
    exit;
}
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = (int)$user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];
        header("Location: " . ($user["role"] === "teacher" ? "teacher_dashboard.php" : "student_dashboard.php"));
        exit;
    }
    $error = "Invalid email or password. Run setup_demo.php once if you have not created demo users.";
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Online Quiz Generator</title><link rel="stylesheet" href="style.css"></head>
<body><main class="card">
<h1>Online Quiz Generator</h1><p class="muted">Sign in as a teacher or student.</p>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<label>Email</label><input type="email" name="email" required autocomplete="username">
<label>Password</label><input type="password" name="password" required autocomplete="current-password">
<button type="submit">Login</button>
</form>
<p class="muted small">First time? Import database.sql, then open setup_demo.php once.</p>
</main></body></html>
