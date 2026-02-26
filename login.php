<?php
require __DIR__ . "/config/db.php";
session_start();

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user"] = $user;
        header("Location: dashboard.php");
        exit;
    } else {
        $msg = "Invalid login.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="card" style="max-width:400px;margin:80px auto;">
  <h2>Login</h2>

  <?php if ($msg): ?>
    <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Email</label>
    <input name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button class="btn btn-dark" style="margin-top:14px;">Login</button>
  </form>
</div>

</body>
</html>