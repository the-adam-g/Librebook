<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

include 'config.php';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedMode = $_POST['mode'];
    $stmt = $pdo->prepare('UPDATE users SET preferred_mode = ? WHERE id = ?');
    $stmt->execute([$selectedMode, $userId]);
    $_SESSION['preferred_mode'] = $selectedMode;
    $selectedMode = $_POST['kmode'];
    $stmt = $pdo->prepare('UPDATE users SET kids = ? WHERE id = ?');
    $stmt->execute([$selectedMode, $userId]);
    $_SESSION['kmode'] = $selectedMode;
}
$stmt = $pdo->prepare('SELECT preferred_mode FROM users WHERE id = ?');
$stmt->execute([$userId]);
$currentMode = $stmt->fetchColumn();
?>
<?php
include 'cmode.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
</head>
<style>
#kids {
    color: green;
}
#dc {
    background-color: #32CD32;
    color: white;
    font-weight: bold;
    border: 2px #32CD32;
    border-radius: 8px;
    text-decoration: none;
    padding: 10px;
}
#dc:hover {
    background-color: #2db92d;
    color: white;
    font-weight: bold;
    border: 2px #2db92d;
    border-radius: 8px;
    padding: 10px;
}
#dd {
    background-color: #DF1616;
    color: white;
    font-weight: bold;
    border: 2px #DF1616;
    border-radius: 8px;
    text-decoration: none;
    padding: 10px; 
}
#dd:hover {
    background-color: #B21111;
    color: white;
    font-weight: bold;
    border: 2px #B21111;
    border-radius: 8px;
    text-decoration: none;
    padding: 10px; 
}
</style>
<body>
    <section id="head">
        <img src="/images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <section id="messages">
    <h1>User Settings</h1>
    <hr>
    <br>
    <a id="dc" href="data-center.php">User Data Center</a>
    <p>Manage your data and messages on librebook</p>
    <hr>
    <form method="post" action="settings.php">
        <p></p>
        <label for="mode">Preferred Mode:</label>
        <select name="mode" id="mode">
            <option value="light" <?php echo ($currentMode === 'light') ? 'selected' : ''; ?>>Light</option>
            <option value="dark" <?php echo ($currentMode === 'dark') ? 'selected' : ''; ?>>Dark</option>
            <option value="blue" <?php echo ($currentMode === 'blue') ? 'selected' : ''; ?>>Blue</option>
            <option value="liberatube" <?php echo ($currentMode === 'liberatube') ? 'selected' : ''; ?>>Liberatube collab</option>
            <option value="nature" <?php echo ($currentMode === 'nature') ? 'selected' : ''; ?>>Mint green - Inspired by Rocky Linux!</option>
            <option value="nothing" <?php echo ($currentMode === 'nothing') ? 'selected' : ''; ?>>De-bloated&trade;</option>
            <option value="opposite" <?php echo ($currentMode === 'opposite') ? 'selected' : ''; ?>>Opposite day - Become thankful for the colour scheme!</option>
	    <option value="tecopa" <?php echo ($currentMode === 'tecopa') ? 'selected' : ''; ?>>Tecopa</option>
        </select>
        <p></p>
        <hr>
        <p></p>
        <a href="deleteyou.php" id="dd">Delete your account</a>
        <p></p>
        <hr>
        <p></p>
        <a href="apikey.php"><button>API key hub</button></a>
        <p></p>
        <hr>
        <p></p>
        <a href="../logout.php"><button>Logout</button></a>
        <p></p>
        <hr>
        <p></p>
        <a href="../profiles/sprofile.php"><button>Edit my profile</button></a>
        <p></p>
        <hr>
        <p></p>
        <?php echo "Kids mode is currently: <h1 style='color: #1877f2;'>" . $_SESSION['kmode'] . "</h1>";?>
        <p></p>
        <hr>
        <p></p>
        <select name="kmode" id="kmode">
            <option value="off" <?php echo ($currentMode === 'off') ? 'selected' : ''; ?>>Off</option>
            <option value="on" <?php echo ($currentMode === 'on') ? 'selected' : ''; ?>>On</option>
        </select>
        <p></p>
        <hr>
        <p></p>
        <button type="submit">Save Changes</button>
    </form>
    <br>
    <a href="../main.php">Go back to main page</a>
    </section>
</body>
</html>
