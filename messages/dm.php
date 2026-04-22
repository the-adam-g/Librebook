<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
} else {
    header('Location: ../index.php');
    exit();
}

include '../config.php';
include '../profiles/cmode.php';
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['currentpass'] = $user['password'];
if ($_SESSION['sudopassword'] != $_SESSION['currentpass']) { //* comparing the cooler password to the one in sql to see if their account still exists
    session_destroy();
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>librebook</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<style>
#reciever {
    color: white;
    padding: 12px;
    max-width: 1000px;
    max-height: 50px;
    border-radius: 4px;
    box-sizing: border-box;
    margin-left: auto;
    margin-right: auto;
    resize: none;
    background-color: #1c1917;
}
</style>
<body>

    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <br>
    <div id="helloworld" style="position: sticky; align-self: flex-start;">
        <?php
        echo 'Welcome back ' . htmlspecialchars($username) . '!';
        ?>
        <p></p>
        <a href="../main.php">Return to main page</a><a href="../settings.php" style="float: right;">Go to Settings</a>
        <p></p>
        <a href="../logout.php">Logout</a><a href="../profiles/sprofile.php" style="float: right;">See my profile</a>
        <p></p>
        <a href="followmes.php">See what the people you follow are saying!</a>
    </div>
    <section id="frlist">
        <h1>Your friends</h1>
        <?php
        try {
            /*
            “user being followed” → followuser_id
            “person who clicked follow” → followinguser_id
            */
            $query = "SELECT followingname FROM following WHERE followuser_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                foreach ($result as $row) {
                    echo '<a style="font-size: 20px;" href="../profiles/rprofiles.php?search=' . htmlspecialchars($row["followingname"], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row["followingname"], ENT_QUOTES, 'UTF-8') . '</a><br>';
                }
            } else {
                echo "No users found.";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>
    </section>
    <section id="messages">
        <h1>Let someone know what you're thinking</h1>
        <?php if (isset($_SESSION['replyto'])) { echo $_SESSION['replyto']; } ?>
        <form id="messageForm">
            <textarea id="reciever" name="reciever" rows="4" cols="50" required placeholder="Receiver name"></textarea>
            <hr>
            <textarea id="message" name="message" rows="4" cols="50" required placeholder="Let someone know what you're thinking"></textarea><p></p><button id="bootun" type="submit">Post</button>
            <hr>
            <button type="button" onclick="updateMessages()">Refresh messages!</button><br><a href="../messages/clearrep.php">Clear reply</a>
        </form>
        <br>
    </section>
    <section id="messages">
    <h1>Your received messages:</h1>
        <div id="success"></div>
        <div id="error"></div>
        <div id="messageList"></div>
    </section>
    <section id="messages">
        <h1>Your sent messages:</h1>
        <div id="smessageList"></div>
    </section>
    <script>
        var userID = <?php echo json_encode($username); ?>;
        function updateMessages() {
            $.ajax({
                type: "GET",
                url: "../messages/dmessages.php",
                success: function (response) {
                    $("#messageList").html(response);
                    console.log('Messages loaded');
                },
                error: function (xhr, status, error) {
                    $("#error").text("An error occurred: " + error).fadeIn().delay(3000).fadeOut();
                }
            });
            $.ajax({
                type: "GET",
                url: "../messages/sdmessages.php",
                success: function (response) {
                    $("#smessageList").html(response);
                    console.log('Messages loaded');
                },
                error: function (xhr, status, error) {
                    $("#error").text("An error occurred: " + error).fadeIn().delay(3000).fadeOut();
                }
            });
        }

$(document).ready(function () {
    $("#messageForm").submit(function (event) {
        event.preventDefault();
        var name = userID;
        var message_text = $("#message").val();
        var reciever = $("#reciever").val();

        if (message_text.trim() === '' || reciever.trim() === '') {
            $("#error").text("Both fields are required!").fadeIn().delay(3000).fadeOut();
            return;
        }

        $.ajax({
            type: "POST",
            url: "../messages/dsubmit.php",
            data: { name: name, reciever: reciever, message_text: message_text },
            success: function (response) {
                $("#message").val("");
                $("#reciever").val("");
                $("#success").text(response).fadeIn().delay(3000).fadeOut();
                updateMessages();
            },
            error: function (xhr, status, error) {
                $("#error").text("An error occurred: " + error).fadeIn().delay(3000).fadeOut();
            }
        });
    });

    updateMessages();
});

    </script>
</body>
</html>

<?php
}
?>
