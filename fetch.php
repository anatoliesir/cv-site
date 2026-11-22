<?php
require_once "config.php";
require_once "session.php";

if (!is_logged_in()){
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $user_id = current_user_id();

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

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($page_repos)) {
            echo "<p style='color: red;'>Error: Invalid JSON response received from GitHub API.</p>";
            echo "<br><a href='index.php'>Back to form</a>";
            exit();
        }

        if (empty($page_repos)) {
            break;
        }

        $all_repositories = array_merge($all_repositories, $page_repos);

        if (count($page_repos) < $per_page) {
            break;
        }

        $page++;
    }
    
    $check_gh = $mysqli->prepare("SELECT `id` FROM `users` WHERE `github_username` = ? AND `id` != ?");
    $check_gh->bind_param("si", $username, $user_id);
    $check_gh->execute();
    $check_gh->store_result();

    if ($check_gh->num_rows() > 0){
        $check_gh->close();
        echo "<p style='color: red;'>Error: This GitHub username is already linked to another account.</p>";
        echo "<br><a href='index.php'>Back to profile</a>";
        exit();
    }
    $check_gh->close();


    $stmt_user = $mysqli->prepare("UPDATE `users` SET `github_username` = ? WHERE `id` = ?");
    $stmt_user->bind_param("si", $username, $user_id);
    $stmt_user->execute();
    $stmt_user->close();

    $_SESSION['github_username'] = $username;

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

    header("Location: index.php?user=" . urlencode($username) . "&fetched=1");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>