<?php
if (!file_exists(__DIR__ . '/.env')){
    die("Error: .env file is missing. Please create one based on .env.example.");
}

$env = parse_ini_file(__DIR__ . '/.env');

$host = $env['DB_HOST'];
$username = $env['DB_USER'];
$password = $env['DB_PASS'];
$dbname = $env['DB_NAME'];

$mysqli = new mysqli($host, $username, $password);

if ($mysqli -> connect_error){
    die("Connection failed: " . $mysqli -> connect_error);
}

$sql_db = "CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($sql_db) === TRUE){
    $mysqli->select_db($dbname);
} else{
    die("Error creating database: " . $mysqli->error);
}

$sql_users_table = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `github_username` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$mysqli->query($sql_users_table)) {
    die("Error creating users table: " . $mysqli->error);
}

$sql_projects_table = "CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `repo_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `repo_url` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_repo` (`user_id`, `repo_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$mysqli->query($sql_projects_table)) {
    die("Error creating projects table: " . $mysqli->error);
}

?>