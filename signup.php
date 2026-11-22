<?php
require_once "config.php";
require_once "session.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6){
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password){
        $error = "Passwords do not match.";
    } else{
        $check = $mysqli->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows() > 0) {
            $error = "An account with this email already exists.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $mysqli->prepare("INSERT INTO `users` (`email`, `password_hash`) VALUES (?, ?)");
            $insert->bind_param("ss", $email, $password_hash);
            
            if ($insert->execute()){
                session_regenerate_id(true);
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['user_email'] = $email;
                header("Location: index.php");
                exit();
            } else{
                $error = "Could not create account. Please try again.";
            }
            $insert->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>        
        <form method="POST" action="signup.php">
            <label>Email:</label><br>
            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <label>Confirm Password:</label><br>
            <input type="password" name="confirm_password" required><br><br>

            <button type="submit">Sign Up</button>
        </form>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <p>Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>