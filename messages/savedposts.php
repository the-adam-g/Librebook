<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['savepost'])) {
    savepost($_GET['savepost']);
}

include '../cmode.php';
include '../config.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
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
</head>
<body>
    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <br>
    <div id="helloworld" style="position: sticky; align-self: flex-start; float: left;">
        <h1>Return to the main page</h1>
        <button onclick='location="../main.php"'>Take me back!</button>
    </div>
<section id="messages" style="flex-grow: 1;">
<?php

function extractVideoId($url) {
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'], $query);
    return isset($query['v']) ? $query['v'] : null;
}
function savepost($id) { //lowkey just copied the following user logic here. Adam Gillion - 05/08/2025 12:04 UTC
    global $pdo;
    $username = $_SESSION['username'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET saved = 
            CASE 
                WHEN saved LIKE CONCAT('%', ?, '%') THEN 
                    TRIM(BOTH ', ' FROM REPLACE(REPLACE(CONCAT(', ', saved, ', '), ', ,', ','), CONCAT(', ', ?, ', '), ', '))
                WHEN saved = '' THEN 
                    ?
                ELSE 
                    CONCAT(saved, ', ', ?)
            END 
            WHERE username = ?");
        
        $stmt->execute([$id, $id, $id, $id, $username]);

        header("Location: savedposts.php");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
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

function convertNameToLink($name) {
    return '<a href="../profiles/rprofiles.php?search=' . urlencode($name) . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
}
$stmt = $pdo->prepare("SELECT saved FROM users WHERE username = ?");
$stmt->execute([$username]);
$posts = $stmt->fetchColumn();

if ($posts !== null && trim($posts) !== '') {
    $savedList = array_filter(array_map('trim', explode(',', $posts))); // trim and remove empty entries
    if (count($savedList) > 0) {
        echo "<h1>Saved posts for " . htmlspecialchars($username) . ": </h1>";
        foreach ($savedList as $post) {
            $stmt = $pdo->prepare("SELECT name, message, timestamp FROM messages WHERE id = ?");
            $stmt->execute([$post]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['message'])) {
                $name = $result['name'];
                $nameLink = convertNameToLink($name);
                $message = htmlspecialchars($result['message']);
                echo "<p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b> {$message}</p>";
                echo "<p style='font-size: 0.9em; color: #888; margin: 0 0 10px;'>" . htmlspecialchars($result['timestamp']) . "</p>";
                $id = $post;
                echo '<a href="savedposts.php?savepost=' . $id . '"><button>Unsave Post</button></a><hr>';
            }
        }
    } else {
        echo "User " . htmlspecialchars($username) . " has no saved posts.";
    }
} else {
    echo "User " . htmlspecialchars($username) . " has no saved posts.";
}

?>
</section>
