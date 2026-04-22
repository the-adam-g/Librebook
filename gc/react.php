<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
} else {
    header('Location: ../index.php');
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
include '../cmode.php';
include '../config.php';
$chatid = intval($_GET['gcid']);
$mesid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$emoji = isset($_GET['react']) ? $_GET['react'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>librebook</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <br>
    <div id="helloworld">
        <?php echo 'Welcome back ' . htmlspecialchars($username) . '!'; ?>
        <p></p>
        <a href="../deleteyou.php">Delete your account</a>
        <a href="../settings.php" style="float: right;">Go to Settings</a>
        <p></p>
        <a href="../logout.php">Logout</a>
        <a href="../profiles/sprofile.php" style="float: right;">See my profile</a>
        <p></p>
        <a href="../main.php">Take me back!</a>
    </div>
    <section id="messages">
    <?php
    $query = $pdo->prepare("SELECT * FROM reactions WHERE messageid = ? AND username = ? AND reaction = ?");
    $query->execute([$mesid, $username, $emoji]);
    if ($query->rowCount() > 0) {
        $query = $pdo->prepare("DELETE FROM reactions WHERE messageid = ? AND username = ? AND reaction = ?");
        $query->execute([$mesid, $username, $emoji]);
        echo "Reaction removed.";
        header('Location: gcchat.php?gcid=' . $chatid);
    } else {
        $query = $pdo->prepare("INSERT INTO reactions (`messageid`, `username`, `reaction`) VALUES (?, ?, ?)");
        $query->execute([$mesid, $username, $emoji]);
        echo "Reaction added.";
        header('Location: gcchat.php?gcid=' . $chatid);
    }
    ?>
    </section>
</body>
</html>


