<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config.php';
include '../profiles/cmode.php';

$chatid   = intval($_GET['gcid']);
$userId   = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

if (!$userId) {
    die("You must be logged in to join a group chat.");
}

$stmt2 = $pdo->prepare("SELECT owner, gcname FROM gc_ids WHERE chatid = :chatid");
$stmt2->bindParam(':chatid', $chatid, PDO::PARAM_INT);
$stmt2->execute();
$gcinfo = $stmt2->fetch(PDO::FETCH_ASSOC);
$gcname = $gcinfo['gcname'];
$owner = $gcinfo['owner'];

if ($username !== $owner) {
    header("Location: gc.php");
    exit();
}

if (isset($_POST['kick_specuser']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $sql = "DELETE FROM gc_members WHERE chatid = :chatid AND id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':chatid', $chatid, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo "<script>alert('The specified user has been kicked successfully.');</script>";
        header("Refresh:0");
        exit();
    } else {
        echo "<script>alert('Error kicking the specified user.');</script>";
        unset($id);
    }
} else if (isset($_POST['change_password'])) {
    $cpassword = $_POST['cpassword'];
    $npassword = $_POST['npassword'];
    $conpassword = $_POST['conpassword'];
        
    if (empty($cpassword) || empty($npassword) || empty($conpassword)) {
        $message = '<p style="color: red;">All password fields are required.</p>';
    } elseif ($npassword !== $conpassword) {
        $message = '<p style="color: red;">New passwords do not match.</p>';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM gc_ids WHERE chatid = ?");
        $stmt->execute([$chatid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        if ($user && password_verify($cpassword, $user['password'])) {
            $hashedPassword = password_hash($npassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE gc_ids SET password = ? WHERE chatid = ?");
            $stmt->execute([$hashedPassword, $chatid]);
                
            if ($stmt->rowCount() > 0) {
                $message = '<p style="color: green;">Password updated successfully!</p>';
            } else {
                $message = '<p style="color: red;">Error updating password.</p>';
            }
        } else {
            $message = '<p style="color: red;">Current password is incorrect.</p>';
        }
    }
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
    <?php 
    echo '<h1> Your groupchat: ' . $gcinfo['gcname'] . '</h1>';
    echo '<br>';
    $stmt = $pdo->prepare("SELECT id FROM gc_members WHERE chatid = :chatid");
    $stmt->bindParam(':chatid', $chatid, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        foreach ($result as $row) {
            $stmt2 = $pdo->prepare("SELECT username FROM users WHERE id = :id");
            $stmt2->bindParam(':id', $row['id'], PDO::PARAM_INT);
            $stmt2->execute();
            $gcusers = $stmt2->fetchColumn();
            echo "<form action='' method='post'>";
            echo htmlspecialchars($gcusers) . '<br> <input type="hidden" name="id" value="' . $row['id'] . '">';
            echo "<input type='submit' id='bu1' name='kick_specuser' value='Kick This User'>";
            echo '</form>';
        }
    } else {
        echo "<p>An unknown error occured.</p>";
    }
    ?>
    <br>
    <hr>
    <a href="../main.php">Go back to main page</a>
    </section>
    <section id="messages">
        <h1>Change password</h1>
        <form method="post" action="">
            <label for="cpassword">Current Password:</label>
            <input type="password" name="cpassword" id="cpassword" required> 
            <br>
            <label for="npassword">New Password:</label>
            <input type="password" name="npassword" id="npassword" required>
            <br>
            <label for="conpassword">Confirm New Password:</label>
            <input type="password" name="conpassword" id="conpassword" required>
            <br>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </section>
</body>
</html>
