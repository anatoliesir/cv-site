<?php
require_once "config.php";
require_once "session.php";

$logged_user_id = current_user_id();
$logged_email = $_SESSION['user_email'] ?? null;

$target_user = null;

if (!empty($_GET['user'])) {
    $gh_param = trim($_GET['user']);
    $stmt = $mysqli->prepare("SELECT `id`, `email`, `github_username` FROM `users` WHERE `github_username` = ?");
    $stmt->bind_param("s", $gh_param);
    $stmt->execute();
    $target_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif (!empty($_GET['id']) && ctype_digit($_GET['id'])) {
    $id_param = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT `id`, `email`, `github_username` FROM `users` WHERE `id` = ?");
    $stmt->bind_param("i", $id_param);
    $stmt->execute();
    $target_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($logged_user_id !== null) {
    $stmt = $mysqli->prepare("SELECT `id`, `email`, `github_username` FROM `users` WHERE `id` = ?");
    $stmt->bind_param("i", $logged_user_id);
    $stmt->execute();
    $target_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $res = $mysqli->query("SELECT `id`, `email`, `github_username` FROM `users` ORDER BY `id` ASC LIMIT 1");
    if ($res) {
        $target_user = $res->fetch_assoc();
    }
}

$is_owner = ($target_user !== null && $logged_user_id !== null && (int)$target_user['id'] === $logged_user_id);

$cv = [
    'full_name' => '',
    'headline' => '',
    'summary' => '',
    'experience' => '',
    'education' => '',
    'skills' => '',
    'contact_email' => '',
    'website' => '',
];
$projects = [];

if ($target_user !== null) {
    $target_id = (int)$target_user['id'];

    $cv_stmt = $mysqli->prepare("SELECT `full_name`, `headline`, `summary`, `experience`, `education`, `skills`, `contact_email`, `website`
                                 FROM `cv_profiles` WHERE `user_id` = ?");
    $cv_stmt->bind_param("i", $target_id);
    $cv_stmt->execute();
    $cv_row = $cv_stmt->get_result()->fetch_assoc();
    $cv_stmt->close();

    if ($cv_row) {
        $cv = array_merge($cv, $cv_row);
    }

    $proj_stmt = $mysqli->prepare("SELECT `repo_name`, `description`, `repo_url`
                                   FROM `projects` WHERE `user_id` = ? ORDER BY `repo_name` ASC");
    $proj_stmt->bind_param("i", $target_id);
    $proj_stmt->execute();
    $proj_res = $proj_stmt->get_result();
    while ($row = $proj_res->fetch_assoc()) {
        $projects[] = $row;
    }
    $proj_stmt->close();
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_path = $_SERVER['PHP_SELF'] ?? '/index.php';
$share_query = '';
if ($target_user !== null) {
    $share_query = !empty($target_user['github_username'])
        ? '?user=' . urlencode($target_user['github_username'])
        : '?id=' . (int)$target_user['id'];
}
$share_url = $scheme . '://' . $host . $script_path . $share_query;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($cv['full_name'] ?: ($target_user['github_username'] ?? 'Portfolio & CV')) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="auth-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid #ddd;">
        <div>
            <a href="index.php">Home</a>
        </div>
        <div>
            <?php if ($logged_user_id !== null): ?>
                <span>Logged in as <strong><?= htmlspecialchars($logged_email ?? '') ?></strong></span> |
                <a href="index.php?id=<?= $logged_user_id ?>">My Profile</a> |
                <a href="logout.php">Log Out</a>
            <?php else: ?>
                <a href="login.php">Log In</a> |
                <a href="signup.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_GET['fetched'])): ?>
        <p style="color: green;">GitHub repositories synced successfully!</p>
    <?php endif; ?>
    <?php if (!empty($_GET['cv_saved'])): ?>
        <p style="color: green;">CV profile saved successfully!</p>
    <?php endif; ?>

    <?php if ($target_user !== null): ?>
        <div class="share-box" style="margin-bottom:20px; padding:10px; background:#f5f5f5; border-radius:6px;">
            <label for="share-link"><strong>Shareable CV & Portfolio Link:</strong></label><br>
            <input id="share-link" type="text" readonly value="<?= htmlspecialchars($share_url) ?>" style="width:75%; padding:6px;">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('share-link').value)">Copy Link</button>
        </div>

        <article class="cv-document" style="padding:20px; border:1px solid #ccc; border-radius:8px; margin-bottom:25px;">
            <header style="margin-bottom:15px;">
                <h1 style="margin:0;"><?= htmlspecialchars($cv['full_name'] ?: ($target_user['github_username'] ?? 'Developer')) ?></h1>
                <?php if (!empty($cv['headline'])): ?>
                    <p style="margin:4px 0; font-size:1.1em; color:#555;"><?= htmlspecialchars($cv['headline']) ?></p>
                <?php endif; ?>
                <p style="margin:6px 0 0; font-size:0.95em;">
                    <?php if (!empty($cv['contact_email'])): ?>
                        <span>Email: <?= htmlspecialchars($cv['contact_email']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cv['website'])): ?>
                        <?= !empty($cv['contact_email']) ? ' | ' : '' ?>
                        <a href="<?= htmlspecialchars($cv['website']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($cv['website']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($target_user['github_username'])): ?>
                        <?= (!empty($cv['contact_email']) || !empty($cv['website'])) ? ' | ' : '' ?>
                        <a href="https://github.com/<?= urlencode($target_user['github_username']) ?>" target="_blank" rel="noopener noreferrer">
                            GitHub (@<?= htmlspecialchars($target_user['github_username']) ?>)
                        </a>
                    <?php endif; ?>
                </p>
            </header>

            <?php if (!empty($cv['summary'])): ?>
                <section style="margin-bottom:15px;">
                    <h2>Summary</h2>
                    <p><?= nl2br(htmlspecialchars($cv['summary'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($cv['experience'])): ?>
                <section style="margin-bottom:15px;">
                    <h2>Experience</h2>
                    <p><?= nl2br(htmlspecialchars($cv['experience'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($cv['education'])): ?>
                <section style="margin-bottom:15px;">
                    <h2>Education</h2>
                    <p><?= nl2br(htmlspecialchars($cv['education'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($cv['skills'])): ?>
                <section style="margin-bottom:15px;">
                    <h2>Skills</h2>
                    <p><?= nl2br(htmlspecialchars($cv['skills'])) ?></p>
                </section>
            <?php endif; ?>

            <section>
                <h2>Projects &amp; Experience from GitHub</h2>
                <?php if (count($projects) > 0): ?>
                    <ul>
                        <?php foreach ($projects as $proj): ?>
                            <li style="margin-bottom:10px;">
                                <a href="<?= htmlspecialchars($proj['repo_url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <strong><?= htmlspecialchars($proj['repo_name']) ?></strong>
                                </a>
                                <p style="margin:3px 0 0;"><?= htmlspecialchars($proj['description'] ?? 'No description provided.') ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No GitHub projects imported yet.</p>
                <?php endif; ?>
            </section>
        </article>

        <?php if ($is_owner): ?>
            <hr style="margin:30px 0;">
            <h2>Sync GitHub Projects</h2>
            <form method="POST" action="fetch.php" style="margin-bottom:30px;">
                <label for="gh_username">GitHub Username:</label><br>
                <input id="gh_username" type="text" name="username" value="<?= htmlspecialchars($target_user['github_username'] ?? '') ?>" required>
                <button type="submit">Fetch / Sync Repos</button>
            </form>

            <h2>Edit Your CV</h2>
            <form method="POST" action="save_cv.php">
                <label>Full Name:</label><br>
                <input type="text" name="full_name" value="<?= htmlspecialchars($cv['full_name']) ?>" style="width:100%;"><br><br>

                <label>Headline / Role:</label><br>
                <input type="text" name="headline" value="<?= htmlspecialchars($cv['headline']) ?>" placeholder="e.g. Junior PHP &amp; Game Developer" style="width:100%;"><br><br>

                <label>Contact Email:</label><br>
                <input type="text" name="contact_email" value="<?= htmlspecialchars($cv['contact_email']) ?>" style="width:100%;"><br><br>

                <label>Website / Portfolio URL:</label><br>
                <input type="text" name="website" value="<?= htmlspecialchars($cv['website']) ?>" style="width:100%;"><br><br>

                <label>Summary:</label><br>
                <textarea name="summary" rows="4" style="width:100%;"><?= htmlspecialchars($cv['summary']) ?></textarea><br><br>

                <label>Experience:</label><br>
                <textarea name="experience" rows="5" style="width:100%;"><?= htmlspecialchars($cv['experience']) ?></textarea><br><br>

                <label>Education:</label><br>
                <textarea name="education" rows="4" style="width:100%;"><?= htmlspecialchars($cv['education']) ?></textarea><br><br>

                <label>Skills:</label><br>
                <textarea name="skills" rows="3" style="width:100%;" placeholder="e.g. PHP, MySQL, Java, GDScript, Git"><?= htmlspecialchars($cv['skills']) ?></textarea><br><br>

                <button type="submit">Save CV</button>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <p>No profile found yet. <a href="signup.php">Create an account</a> or <a href="login.php">log in</a> to build your CV and portfolio.</p>
    <?php endif; ?>
</div>
</body>
</html>