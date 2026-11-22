<?php
require_once "config.php";
require_once "session.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else{
        $stmt = $mysqli->prepare("SELECT `id`, `email`, `password_hash`, `github_username` FROM `users` WHERE `email` = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])){
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['github_username'] = $user['github_username'] ?? null;

            header("Location: index.php");
            exit();
        } else{
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Log In</h1>        
        <form method="POST" action="login.php">
            <label>Email:</label><br>
            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <button type="submit">Log In</button>
        </form>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <p>Need an account? <a href="signup.php">Sign up</a></p>
    </div>
</body>
</html>