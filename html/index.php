<?php
session_start();
require 'config.php';

date_default_timezone_set('America/Denver'); // Set local time to MST/MDT

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Guest';
$is_admin = false;

// Get admin status
if ($user_id) {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $is_admin = (bool)$stmt->fetchColumn();
}

// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_title'], $_POST['post_content'])) {
    if ($user_id && $is_admin) {
        $title = trim($_POST['post_title']);
        $content = trim($_POST['post_content']);
        if ($title !== '' && $content !== '') {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $title, $content]);
            header("Location: index.php");
            exit;
        }
    }
}

// Fetch files (public and user-owned)
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM files WHERE is_public = 1 OR user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->query("SELECT * FROM files WHERE is_public = 1 ORDER BY created_at DESC");
}
$files = $stmt->fetchAll();

// Fetch posts
$posts = $pdo->query("
    SELECT p.*, u.username 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Welcome, <?= htmlspecialchars($username) ?></h2>

<?php if ($user_id): ?>
    <nav>
        <a href="upload.php">Upload File</a> |
        <a href="logout.php">Logout</a>
        <?php if ($is_admin): ?>
            | <a href="admin.php">Admin Panel</a>
        <?php endif; ?>
    </nav>
<?php else: ?>
    <nav>
        <a href="login.php">Login</a> | <a href="register.php">Register</a>
    </nav>
<?php endif; ?>

<hr>

<h3>Your Files</h3>

<?php if ($files): ?>
<table>
    <thead>
        <tr>
            <th>Filename</th>
            <th>Size (KB)</th>
            <th>Uploaded At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($files as $file): ?>
        <tr>
            <td><a href="download.php?id=<?= $file['id'] ?>"><?= htmlspecialchars($file['filename']) ?></a></td>
            <td><?= number_format($file['filesize'] / 1024, 2) ?></td>
            <td>
                <?php
                    $dt = new DateTime($file['created_at'], new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('America/Denver'));
                    echo $dt->format('Y-m-d h:i A');
                ?>
            </td>
            <td>
                <?php if ($user_id === $file['user_id'] || $is_admin): ?>
                    <a href="delete.php?id=<?= $file['id'] ?>" onclick="return confirm('Delete this file?');">Delete</a>
                <?php else: ?>
                    &mdash;
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p>No files to display.</p>
<?php endif; ?>

<hr>

<h3>Posts</h3>

<?php if ($user_id && $is_admin): ?>
<form method="post" style="margin-bottom: 20px;">
    <input type="text" name="post_title" placeholder="Post Title" required style="width: 100%; padding: 8px;"><br><br>
    <textarea name="post_content" placeholder="Post Content" rows="4" required style="width: 100%; padding: 8px;"></textarea><br>
    <button type="submit">Add Post</button>
</form>
<?php endif; ?>

<?php if ($posts): ?>
    <ul>
        <?php foreach ($posts as $post): ?>
            <li style="background:#fff; padding:10px; margin-bottom:10px; border-radius:6px; box-shadow:0 0 5px #ddd;">
                <strong><?= htmlspecialchars($post['title']) ?></strong>
                — <em>by <?= htmlspecialchars($post['username']) ?> on 
                <?php
                    $dt = new DateTime($post['created_at'], new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('America/Denver'));
                    echo $dt->format('Y-m-d h:i A');
                ?>
                </em><br>
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No posts yet.</p>
<?php endif; ?>

</body>
</html>
