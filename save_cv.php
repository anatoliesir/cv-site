<?php
require_once "config.php";
require_once "session.php";

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = current_user_id();

    $full_name = trim($_POST['full_name'] ?? '');
    $headline = trim($_POST['headline'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $website = trim($_POST['website'] ?? '');

    $stmt = $mysqli->prepare("INSERT INTO `cv_profiles`
        (`user_id`, `full_name`, `headline`, `summary`, `experience`, `education`, `skills`, `contact_email`, `website`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `full_name` = VALUES(`full_name`),
            `headline` = VALUES(`headline`),
            `summary` = VALUES(`summary`),
            `experience` = VALUES(`experience`),
            `education` = VALUES(`education`),
            `skills` = VALUES(`skills`),
            `contact_email` = VALUES(`contact_email`),
            `website` = VALUES(`website`)");

    $stmt->bind_param(
        "issssssss",
        $user_id,
        $full_name,
        $headline,
        $summary,
        $experience,
        $education,
        $skills,
        $contact_email,
        $website
    );

    if ($stmt->execute()) {
        $stmt->close();

        $u_stmt = $mysqli->prepare("SELECT `github_username` FROM `users` WHERE `id` = ?");
        $u_stmt->bind_param("i", $user_id);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result()->fetch_assoc();
        $u_stmt->close();

        if (!empty($u_res['github_username'])) {
            header("Location: index.php?user=" . urlencode($u_res['github_username']) . "&cv_saved=1");
        } else {
            header("Location: index.php?id=" . $user_id . "&cv_saved=1");
        }
        exit();
    } else {
        $stmt->close();
        die("Error saving CV profile.");
    }
} else {
    header("Location: index.php");
    exit();
}

?>