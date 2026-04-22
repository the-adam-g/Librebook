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
    <?php
    include '../profiles/cmode.php';
    ?>
</head>
<body>

    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <br>
    <div id="helloworld">
        <?php
        echo 'Welcome back ' . htmlspecialchars($username) . '!';
        ?>
        <p></p>
        <a href="../deleteyou.php">Delete your account</a><a href="../settings.php" style="float: right;">Go to Settings</a>
        <p></p>
        <a href="../logout.php">Logout</a><a href="../main.php" style="float: right;">Take me to the main page</a>
        <p></p>

    </div>
    <br>
    <section id="messages">
        <div id="success"></div>
        <div id="error"></div>
        <div id="messageList"></div>
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$loginuser = isset($_SESSION['username']) ? $_SESSION['username'] : '';

if (empty($loginuser)) {
    header("Location: ../index.php");
    exit();
}

function extractVideoId($url) {
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'], $query);
    return isset($query['v']) ? $query['v'] : null;
}

function extractID($string) {
    $symbolPosition = strpos($string, '[#@');
    if ($symbolPosition !== false) {
        $substringAfterSymbol = substr($string, $symbolPosition);
        $semicolonPosition = strpos($substringAfterSymbol, ';');
        if ($semicolonPosition !== false) {
            $numbers = substr($substringAfterSymbol, 3, $semicolonPosition - 3);
            $numbers = preg_replace("/[^0-9]/", "", $numbers);
            $replacement = "<a href='../messages/spmessages.php/?id=$numbers'>Reply to</a>";
            $string = substr_replace($string, $replacement, $symbolPosition, $semicolonPosition + 1);
        }
    }

    return $string;
}
function convertHashtagsToLinks($message) {
    $pattern = '/#(\w+)/';
    $messageWithLinks = preg_replace($pattern, '<a href="../messages/hashtag.php?tag=$1">#$1</a>', $message);
    return $messageWithLinks;
}

try {
    /*
    “user being followed” → followuser_id
    “person who clicked follow” → followinguser_id
    */
    $stmt = $pdo->prepare("SELECT m.* FROM messages m JOIN following f ON m.userid = f.followuser_id WHERE f.followinguser_id = ? ORDER BY m.timestamp DESC");
    $stmt->execute([$userId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        foreach ($result as $row) {
            $name = htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars($row["message"], ENT_QUOTES, 'UTF-8');
            $timestamp = $row["timestamp"];
            $id = $row["id"];
            
            $message = extractID($message);
            $message = convertHashtagsToLinks($message);

            if (filter_var($message, FILTER_VALIDATE_URL) && 
                (strpos($message, '.jpg') !== false || 
                strpos($message, '.jpeg') !== false || 
                strpos($message, '.png') !== false || 
                strpos($message, '.webp') !== false)) {
                echo "<div id='imag'><b>{$name}:</b> <br> <img src='{$message}' alt='Image' style='max-width: 600px; height: 100%; max-height: 600px;'> <br> (Sent on: {$timestamp})</div>";
                echo "<hr>";
            } elseif (strpos($message, 'https://ltbeta.epicsite.xyz/videodata/non-hls.php?id=https://ltbeta.epicsite.xyz/videodata/non-hls.php?id=') !== false) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://ltbeta.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "<div><b>{$name}:</b> <br> <video controls><source src='{$videoUrl}' type='video/mp4'></video> <br> (Sent on: {$timestamp})</div>";
                    echo "<hr>";
                }
            } elseif (strpos($message, 'https://www.youtube.com/watch?v=') !== false) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://lt.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "<div><b>{$name}:</b> <br> <video controls><source src='{$videoUrl}' type='video/mp4'></video> <br> (Sent on: {$timestamp})</div>";
                    echo "<hr>";
                }
            } elseif (strpos($message, 'https://lt.epicsite.xyz/watch/?v=') !== false) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://lt.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "<div><b>{$name}:</b> <br> <video controls><source src='{$videoUrl}' type='video/mp4'></video> <br> (Sent on: {$timestamp})</div>";
                    echo "<hr>";
                }
            } elseif (strstr($message, "|| reply - ")) {
                    echo "<div><b>{$name}:</b> Reply to <a href='/messages/spmessages.php/?id=". $oid . "'>" . $oname . "</a> {$message} (Sent on: {$timestamp})</div>";
                    echo " <a href='../messages/reply.php?name=" . urlencode($name) . "&message=" . urlencode($message) . "&timestamp=" . urlencode($timestamp) . "&id=" . urlencode($id) . "'>Reply</a>";
                    echo "<hr>";
            } else {
                echo "<div><b>{$name}:</b> {$message} (Sent on: {$timestamp})</div>";
                echo " <a href='../messages/reply.php'>Reply</a>";
                echo "<hr>";
            }
        }
    } else {
        echo "No messages.";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
    </section>
    <br>
</body>
</html>
<?php
}
?>
