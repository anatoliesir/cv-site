
<?php

require_once "config.php";

$env = parse_ini_file(__DIR__ . '/.env');
$defalut_username = $env['GITHUB_USERNAME'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Portfolio & CV</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>GitHub Portfolio Generator</h1>

    <form action="fetch.php" method="POST" class="form-group">
        <label for="username">Enter your GitHub username:</label><br> <br>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($defalut_username); ?>" required>
        <button type="submit">Fetch and Save Projects</button>
    </form>

    <hr style="margin: 30px 0;">
    <h2>Saved Projects in Portfolio:</h2>

    <?php
    $result = $mysqli->query("SELECT * FROM `projects` ORDER BY `created_at` DESC");

    if ($result && $result->num_rows > 0){
        while ($row = $result->fetch_assoc()){
            echo "<div class='project-card'>";
            echo "<h3><a href='" . htmlspecialchars($row['repo_url']) . "' target='_blank'>" . htmlspecialchars($row['repo_name']) . "</a></h3>";
            echo "<p>" . htmlspecialchars($row['description']) . "</p>";
            echo "<small>Added at: " . $row['created_at'] . "</small>";
            echo "</div>";
        }
    } else{
        echo "<p>No saved projects found. Use the form above to fetch them!</p>";
    }
    ?>
</body>
</html>