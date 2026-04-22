<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config.php';
session_start();
include '../profiles/cmode.php';

$emessage = $_SESSION['emessage'] ?? '';
$username = $_SESSION['username'] ?? null;
unset($_SESSION['emessage']);
function containsEmoji($string) {
    return preg_match('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F1E0}-\x{1F1FF}]/u', $string);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gcname = trim($_POST['gcname']);
    $password = $_POST['password'];
    $vpassword = $_POST['vpassword'];
    if (strpos($gcname, ' ') !== false || strpos($gcname, ',') !== false) {
        $_SESSION['emessage'] = 'Error: Groupchat names cannot contain spaces or commas.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    if (containsEmoji($gcname)) {
        $_SESSION['emessage'] = 'Error: Groupchat names cannot contain emojis.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gc_ids WHERE gcname = ?');
    $stmt->execute([$gcname]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['emessage'] = 'Error: Groupchat name already exists.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    if ($password !== $vpassword) {
        $_SESSION['emessage'] = 'Error: Passwords do not match.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    $hashword = password_hash($password, PASSWORD_BCRYPT);
    $id = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare('INSERT INTO gc_ids (gcname, members, password, owner) VALUES (?, ?, ?, ?)');
    $stmt->execute([$gcname, 1, $hashword, $username]);
    $yourgcid = $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO gc_members (id, chatid) VALUES (?, ?)');
    $stmt->execute([$id, $yourgcid]);
    $_SESSION['emessage'] = "Groupchat created successfully (ID: " . htmlspecialchars($yourgcid) . ")";
    header('Location: gc.php');
    exit();

}

if (isset($_SESSION['user_id'])) {
} else {
    header('Location: ../index.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupchats</title>
</head>
<body>
    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <section id="messages">
        <h1>Create a groupchat</h1>
        <form action="" method="post">
            <p id="error-text"><?= htmlspecialchars($emessage) ?></p>
            Groupchat name: <input type="text" name="gcname" required><br>
            Password: <input type="password" name="password" required><br>
            Verify Password: <input type="password" name="vpassword" required><br>
            <p>By creating a group chat you are agreeing to our <a href="../lpp1.pdf">privacy policy</a></p>
            <input type="submit" id="trinity" value="create">
        </form>
        <br>
        <a href="gc.php">Go back to the main groupchat page</a>
    </section>
</body>
</html>
