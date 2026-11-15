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

    if ($http_code === 200) {
        $repositories = json_decode($response, true);

        foreach ($repositories as $repo){
            $repo_name = $mysqli->real_escape_string($repo['name']);
            $description = $mysqli->real_escape_string($repo['description'] ?? 'No description provided.');
            $repo_url = $mysqli->real_escape_string($repo['html_url']);

            $sql = "INSERT INTO `projects` (`repo_name`, `description`, `repo_url`) 
                    VALUES ('$repo_name', '$description', '$repo_url')
                    ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `repo_url` = VALUES(`repo_url`)";

            $mysqli->query($sql);
        }

        header("Location: index.php?success=1");
        exit();
    } else{
        echo "<p style='color: red;'>Error: Could not find user <strong>" . htmlspecialchars($username) . "</strong> on GitHub or the API rejected the request (HTTP Code: $http_code).</p>";
        echo "<br><a href='index.php'>Back to form</a>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>