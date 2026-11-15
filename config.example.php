<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "github_portfolio";

if (file_exists('config.local.php')){
    require_once 'config.local.php';
}


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

$sql_table = "CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `repo_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `repo_url` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql_table) === TRUE){
} else{
    die("Error creating table: " . $mysqli->error);
}

?>