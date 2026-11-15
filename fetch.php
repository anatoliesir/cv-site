<?php
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) die("Error: Username cannot be empty.");

    $api_url = "https://api.github.com/users/{$username}/repos";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200){
        echo "<p style='color: red;'>Error: Could not find user <strong>" . htmlspecialchars($username) . "</strong> on GitHub or the API rejected the request (HTTP Code: $http_code).</p>";
        echo "<br><a href='index.php'>Back to form</a>";
        exit();
    }

    
    $stmt = $mysqli->prepare("SELECT id FROM `users` WHERE `github_username` = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];
    } else{
        $insert_user = $mysqli->prepare("INSERT INTO `users` (`github_username`) VALUE (?)");
        $insert_user->bind_param("s", $username);
        $insert_user->execute();
        $user_id = $insert_user->insert_id;
        $insert_user->close();
    }
    $stmt->close();

    $repositories = json_decode($response, true);
    $stmt_repo = $mysqli->prepare("INSERT INTO `projects` (`user_id`, `repo_name`, `description`, `repo_url`) 
                                    VALUES (?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `repo_url` = VALUES(`repo_url`)");                        
    foreach ($repositories as $repo){
        $repo_name = $repo['name'];
        $description = $repo['description'] ?? 'No description provided.';
        $repo_url = $repo['html_url'];

        $stmt_repo->bind_param("isss", $user_id, $repo_name, $description, $repo_url);
        $stmt_repo->execute();
    }
    $stmt_repo->close();

    header("Location: index.php?user=" . urlencode($username) . "&success=1");
    exit();
    } 
else {
    header("Location: index.php");
    exit();
}
?>