<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $filename = basename($_FILES['file']['name']);
    $filedata = file_get_contents($_FILES['file']['tmp_name']);
    $filesize = $_FILES['file']['size'];
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];

    if ($filedata) {
        $stmt = $pdo->prepare("INSERT INTO files (filename, content, filesize, user_id, is_public) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $filename);
        $stmt->bindParam(2, $filedata, PDO::PARAM_LOB);
        $stmt->bindParam(3, $filesize, PDO::PARAM_INT);
        $stmt->bindParam(4, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(5, $is_public, PDO::PARAM_BOOL);
        $stmt->execute();

        echo "Uploaded successfully.<br>";
    } else {
        echo "Failed to read uploaded file.<br>";
    }
}
?>
<link rel="stylesheet" href="style.css">
<h2>Upload File</h2>
<form method="post" enctype="multipart/form-data">
    File: <input type="file" name="file" required><br>
    <label><input type="checkbox" name="is_public"> Make Public</label><br>
    <input type="submit" value="Upload">
</form>
<a href="index.php">Back to Files</a>
