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
include '../config.php';

function extractID($string) {
    $symbolPosition = strpos($string, '[#@');
    if ($symbolPosition !== false) {
        $substringAfterSymbol = substr($string, $symbolPosition);
        $semicolonPosition = strpos($substringAfterSymbol, ';');
        if ($semicolonPosition !== false) {
            $numbers = substr($substringAfterSymbol, 3, $semicolonPosition - 3);
            $numbers = preg_replace("/[^0-9]/", "", $numbers);
            $replacement = "<a href='spmessages.php/?id=$numbers'>Reply to</a>";
            $string = substr_replace($string, $replacement, $symbolPosition, $semicolonPosition + 1);
        }
    }

    return $string;
}

try {
    $query = "SELECT `sender`, `reciever`, `message`, `timestamp`
              FROM dm
              WHERE `sender` LIKE :sender
              ORDER BY `timestamp` DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':sender', $username, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($result) {
        foreach ($result as $row) {
            $name = htmlspecialchars($row["sender"], ENT_QUOTES, 'UTF-8');
            $rec = htmlspecialchars($row["reciever"], ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars($row["message"], ENT_QUOTES, 'UTF-8');
            $timestamp = $row["timestamp"];
            
            $message = extractID($message);

            echo "<div><b>" . $name . " -></b><b> " . $rec . ":</b> " . $message . " (Sent on: " . $timestamp . ")</div>";
            echo "<hr>";
        }
    } else {
        echo "No messages with this hashtag.";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
