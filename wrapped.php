<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
include 'config.php';
?>
<?php
include 'cmode.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librebook Wrapped</title>
</head>
<style>
#special {
    font-size: 24px;
}
data {
    color: #1877f2;
}
</style>
<body>
    <section id="head">
        <img src="/images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <section id="messages">
        <h1>Librebook Wrapped.</h1>
        <h1>Celebrate your data on Librebook!</h1>
        <br>
        <a href="../main.php">Go back to main page</a>
    </section>
    <section id="messages">
        <h1>Your data on Librebook over the past year.</h1>
        <div id="special">
        <?php
        $query = "SELECT COUNT(*) AS total FROM reactions WHERE userid = ? AND `timestamp` >= NOW() - INTERVAL 1 YEAR";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
        $totalPastYear = (int)$stmt->fetchColumn();
        echo "You have sent <data>{$totalPastYear}</data> reactions since " . date('Y-m-d H:i:s', strtotime('-1 year'));
        echo "<br>";
        echo "<br>";
        $query = "SELECT COUNT(*) AS total FROM messages WHERE userid = ? AND `timestamp` >= NOW() - INTERVAL 1 YEAR";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
        $totalPastYear = (int)$stmt->fetchColumn();
        echo "You have sent <data>{$totalPastYear}</data> messages since " . date('Y-m-d H:i:s', strtotime('-1 year'));
        echo "<br>";
        echo "<br>";
        $query = "SELECT COUNT(*) AS total FROM following WHERE followinguser_id = ? AND `timestamp` >= NOW() - INTERVAL 1 YEAR";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
        $totalPastYear = (int)$stmt->fetchColumn();
        echo "You have followed <data>{$totalPastYear}</data> user(s) since " . date('Y-m-d H:i:s', strtotime('-1 year'));
        ?>
        </div>
    </section>
</body>
