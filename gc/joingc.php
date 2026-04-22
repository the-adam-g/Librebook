<?php
ob_start(); // output buffering to prevent header errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config.php';
include '../profiles/cmode.php';
$chatid = intval($_GET['gcid']);
$userId   = $_SESSION['user_id'] ?? null;
$stmt2 = $pdo->prepare("SELECT gcname FROM gc_ids WHERE chatid = :chatid");
$stmt2->bindParam(':chatid', $chatid, PDO::PARAM_INT);
$stmt2->execute();
$gcname = $stmt2->fetchColumn();
$stmt3 = $pdo->prepare("SELECT password FROM gc_ids WHERE chatid = :chatid");
$stmt3->bindParam(':chatid', $chatid, PDO::PARAM_INT);
$stmt3->execute();
$gcpassword = $stmt3->fetchColumn();

if (!$userId) {
    die("You must be logged in to join a group chat.");
}

if (isset($_SESSION['user_id'])) {
} else {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $userattempt = $_POST["password"];
        if (password_verify($userattempt, $gcpassword)) {
            $stmt5 = $pdo->prepare('SELECT COUNT(*) FROM gc_members WHERE chatid = ? AND id = ?');
            $stmt5->execute([$chatid, $userId]);
            if ($stmt5->fetchColumn() > 0) {
                echo('Error: You are already in this groupchat!');
                exit();
            } else {
                $stmt4 = $pdo->prepare("INSERT INTO gc_members (id, chatid) VALUES (?, ?)");
                $stmt4->execute([$userId, $chatid]);
                header("Location: gc.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join a groupchat</title>
</head>
<body>
<section id="head">
    <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
    <h1 id="headl">Librebook</h1>
</section>
<section id="messages">
    <h1>Enter password for <?php echo($gcname);?></h1>
    <form action="" method="post">
        Password: <input type="text" name="password" required><br>
        <input type="submit" id="trinity" value="Enter password">
    </form>
    <br>
    <a href="../main.php">Go back to main page</a>
</section>
</body>
</html>
<?php
ob_end_flush(); // send output
?>
