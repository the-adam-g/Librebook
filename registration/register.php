<?php
include '../config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
function containsEmoji($string) {
    return preg_match('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F1E0}-\x{1F1FF}]/u', $string);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $vpassword = $_POST['vpassword'];
    $defaultMode = 'light';
    $kidsv = isset($_POST['kidsm']) ? 'on' : 'off';
    if (strpos($username, ' ') !== false || strpos($username, ',') !== false) {
        $_SESSION['error_message'] = 'Error: Usernames cannot contain spaces or commas.';
        header('Location: erroreg.php');
        exit();
    }
    if (containsEmoji($username)) {
        $_SESSION['error_message'] = 'Error: Usernames cannot contain emojis.';
        header('Location: erroreg.php');
        exit();
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = 'Error: Username already exists.';
        header('Location: erroreg.php');
        exit();
    }
    if ($password !== $vpassword) {
        $_SESSION['error_message'] = 'Error: Passwords do not match.';
        header('Location: erroreg.php');
        exit();
    }
    $hashword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, preferred_mode, kids) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $hashword, $defaultMode, $kidsv]);
    $stmtProfile = $pdo->prepare('INSERT INTO profiles (username, pfp, bio) VALUES (?, ?, ?)');
    $stmtProfile->execute([$username, '../images/empty.webp', '']);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['sudopassword'] = $user['password'];
        $_SESSION['kmode'] = $user['kids'];
        header('Location: ../main.php');
        exit();
    } else {
        $_SESSION['error_message'] = 'Error: Unable to log in after registration.';
        header('Location: erroreg.php');
        exit();
    }
}
?>
