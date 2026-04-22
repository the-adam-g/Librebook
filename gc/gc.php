<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config.php';
session_start();
include '../profiles/cmode.php';
$id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
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
    <h1>Your groupchats:</h1>
    <?php
    $stmt = $pdo->prepare("SELECT chatid FROM gc_members WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        foreach ($result as $row) {
            $stmt2 = $pdo->prepare("SELECT gcname, owner FROM gc_ids WHERE chatid = :chatid");
            $stmt2->bindParam(':chatid', $row['chatid'], PDO::PARAM_INT);
            $stmt2->execute();
            $gcids = $stmt2->fetch(PDO::FETCH_ASSOC);

            $gcname = $gcids['gcname'];
            $owner = $gcids['owner'];

            if ($gcname) {
                if ($username === $owner) {
                    echo "<a href='gcchat.php?gcid=" . $row['chatid'] ."'>" . htmlspecialchars($gcname) . "</a> - <a href='joingc.php?gcid=" . $row['chatid'] ."'>Invite link</a> - <a href='gccontrol.php?gcid=" . $row['chatid'] ."'>Edit groupchat</a>";
                } else {
                    echo "<a href='gcchat.php?gcid=" . $row['chatid'] ."'>" . htmlspecialchars($gcname) . "</a> - <a href='joingc.php?gcid=" . $row['chatid'] ."'>Invite link</a>";
                }
            }
        }
    } else {
        echo "<p>You are not in any groupchats yet.</p>";
    }
    ?>
    <br>
    <hr>
    <a href="../main.php">Go back to main page</a>
    </section>

    <section id="messages">
        <h1>Create a groupchat!</h1>
        <a href="creategc.php">Create!</a>
    </section>
</body>
</html>
