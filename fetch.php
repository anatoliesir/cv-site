<?php
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) die("Error: Username cannot be empty.");

    $all_repositories = [];
    $page = 1;
    $per_page = 100;

    while (true) {
        $api_url = "https://api.github.com/users/" . urlencode($username) . "/repos?per_page={$per_page}&page={$page}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);    

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            echo "<p style='color: red;'>Network Error: Could not connect to GitHub (" . htmlspecialchars($curl_error) . ").</p>";
            echo "<br><a href='index.php'>Back to form</a>";
            exit();
        }

        if ($http_code !== 200) {
            echo "<p style='color: red;'>Error: Could not find user <strong>" . htmlspecialchars($username) . "</strong> on GitHub or API rejected the request (HTTP Code: $http_code).</p>";
            echo "<br><a href='index.php'>Back to form</a>";
            exit();
        }

        $page_repos = json_decode($response, true);

        if (!is_array($page_repos) || empty($page_repos)) {
            break; 
        }

        $all_repositories = array_merge($all_repositories, $page_repos);

        if (count($page_repos) < $per_page) {
            break;
        }

        $page++;
    }
    
    $stmt_user = $mysqli->prepare("INSERT INTO `users` (`github_username`) VALUES (?) 
                                   ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`)");
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $user_id = $mysqli->insert_id;
    $stmt_user->close();

    $stmt_repo = $mysqli->prepare("INSERT INTO `projects` (`user_id`, `repo_name`, `description`, `repo_url`) 
                                   VALUES (?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `repo_url` = VALUES(`repo_url`)");
                        
    foreach ($all_repositories as $repo) {
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