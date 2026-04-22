<?php
ob_start(); // output buffering to prevent header errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config.php';
include '../cmode.php';

if (isset($_SESSION['user_id'])) {
} else {
    header('Location: ../index.php');
    exit();
}

$userId   = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$pagenum  = $_SESSION['page'] ?? 1;
$kmode    = $_SESSION['kmode'] ?? 'off';
$offsetnum = 10;

function extractVideoId($url) {
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'] ?? '', $query);
    return $query['v'] ?? null;
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

function savepost($id) {
    global $pdo, $username;
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
        header("Location: ../main.php");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

function convertHashtagsToLinks($message) {
    return preg_replace('/#(\w+)/', '<a href="hashtag.php?tag=$1">#$1</a>', $message);
}

if (isset($_GET['savepost'])) {
    savepost($_GET['savepost']);
}

if (!isset($_GET['gcid'])) {
    header("Location: gc.php");
    exit;
}
$chatid = intval($_GET['gcid']);
$stmt = $pdo->prepare("SELECT id FROM gc_members WHERE chatid = ?");
$stmt->execute([$chatid]);
$gcmembers = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($userId, $gcmembers)) {
    header("Location: gc.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $name = $_SESSION['username'] ?? null;
        $message_text = $_POST["message"];

        if (empty($name) || empty($message_text)) {
            $msg = "Sender name and message are required!";
        } else {
            $stmt = $pdo->prepare("SELECT kids FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $kids_mode = $stmt->fetchColumn();

            if ($kids_mode == 'on') {
                $forbiddenWords = json_decode(file_get_contents('bad-words.json'), true);
                $lowercase_message_text = strtolower($message_text);
                foreach ($forbiddenWords as $word) {
                    if (strpos($lowercase_message_text, strtolower($word)) !== false) {
                        $msg = "Warning! Your message contained language not allowed in kids mode!";
                        ob_end_flush();
                        echo $msg;
                        exit;
                    }
                }
            }

            if (isset($_SESSION['replyto'])) {
                $message_text = $_SESSION['replyto'] . $message_text;
            }

            $sql = "INSERT INTO gc_messages (chatid, name, message, timestamp) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$chatid, $name, $message_text]);
            unset($_SESSION['replyto']);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?gcid=' . $chatid);
            exit;
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
<title>Groupchats</title>
<style>
.dropbtn {
  color: white;
  font-size: 14px;
  border: none;
  cursor: pointer;
}
.dropdown {
  position: relative;
  display: inline-block;
}
.dropdown-content {
  position: absolute;
  left: 100%;
  top: 0;
  background-color: #1877f2;
  overflow: hidden;
  border-radius: 5px !important;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
  white-space: nowrap;
  opacity: 0;
  transform: translateX(-40px);
  pointer-events: none;
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.dropdown-content.show {
  opacity: 1;
  transform: translateX(0);
  pointer-events: auto;
}
.dropdown-content a {
  display: inline-block;
  padding: 8px 12px;
  text-decoration: none;
  color: black;
}
.dropdown-content a:hover {
  background-color: #115293;
}
</style>
</head>
<body>

<section id="head">
    <img src="/images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
    <h1 id="headl">Librebook</h1>
</section>

<section id="messages">
<?php
try {
    $messagenum = ($pagenum - 1) * $offsetnum;
    $stmt = $pdo->prepare("SELECT messageid, name, message, timestamp, nsfw FROM gc_messages WHERE chatid = :chatid ORDER BY timestamp DESC LIMIT :offset, :limit");
    $stmt->bindParam(':chatid', $chatid, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $messagenum, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $offsetnum, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        foreach ($result as $row) {
            $messageId = $row["messageid"];
            $rawName   = $row["name"];
            $message   = htmlspecialchars($row["message"], ENT_QUOTES, 'UTF-8');
            $timestamp = $row["timestamp"];
            $isNSFW    = $row["nsfw"] ?? 0;
            $vStmt = $pdo->prepare("SELECT verified FROM users WHERE username = ?");
            $vStmt->execute([$rawName]);
            $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
            $isVerif = $vRow["verified"] ?? 0;
            $vname = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');
            if ($isVerif) {
                $vname .= ' <img src=/images/verified2.svg alt="Verified" style="vertical-align: middle; max-width: 18px; max-height: 18px;">';
            }
            $nameLink = '<a href="../profiles/rprofiles.php?search=' . urlencode($rawName) . '">' . $vname . '</a>';
            if ($kmode === 'on') {
                if ($isNSFW) {
                    $message = '[CENSORED BY KIDS MODE: NSFW]';
                } else {
                    $forbiddenWords = json_decode(file_get_contents('bad-words.json'), true);
                    foreach ($forbiddenWords as $word) {
                        $message = preg_replace("/\b" . preg_quote($word, '/') . "\b/i", '[CENSORED BY KIDS MODE]', $message);
                    }
                }
            }
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtCheck->execute([$rawName]);
            $blockID = $stmtCheck->fetchColumn();
            $stmtBlock = $pdo->prepare("SELECT COUNT(*) FROM blocked WHERE blockedID = ? AND userID = ?");
            $stmtBlock->execute([$blockID, $userId]);
            if ($stmtBlock->fetchColumn() == 1) {
                $message = '<p style="color: red;">[YOU HAVE BLOCKED THIS USER]</p>';
            }
            $message = extractID($message);
            $message = convertHashtagsToLinks($message);
            $realdate = date("l M j, Y h:i:s A", strtotime($timestamp));
            $counts = [];
            $reactionStmt = $pdo->prepare("SELECT reaction, COUNT(*) AS total FROM reactions WHERE messageid = ? GROUP BY reaction");
            $reactionStmt->execute([$messageId]);
            while ($r = $reactionStmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[$r['reaction']] = $r['total'];
            }
            $savedPostsString = '';
            if ($username) {
                $stmtSaved = $pdo->prepare("SELECT saved FROM users WHERE username = ?");
                $stmtSaved->execute([$username]);
                $savedPostsString = $stmtSaved->fetchColumn() ?? '';
            }
            $savedPostsArray = array_filter(array_map('trim', explode(',', $savedPostsString)));
            $isSaved = in_array($messageId, $savedPostsArray);
            echo "<div style='border-radius: 8px; margin-bottom: 12px; color: #ccc; font-family: sans-serif;'>";
            if (filter_var($message, FILTER_VALIDATE_URL) &&
                preg_match('/\.(jpg|jpeg|png|webp)$/i', $message)) {
                echo "<p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><img src='{$message}' alt='Image' style='max-width: auto; height: auto; max-height: auto;'></p>";
            } elseif (str_contains($message, 'https://ltbeta.epicsite.xyz/videodata/non-hls.php?id=') ||
                      str_contains($message, 'https://www.youtube.com/watch?v=') ||
                      str_contains($message, 'https://lt.epicsite.xyz/watch/?v=')) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://ltbeta.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "<p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><video controls loading='lazy' poster='http://i.ytimg.com/vi/{$videoId}/mqdefault.jpg'><source src='{$videoUrl}' type='video/mp4'></video></p>";
                }
            } else {
                echo "<p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b> {$message}</p>";
            }
            if ($isNSFW) {
                echo "<p style='color:red;'>NSFW</p>";
            }
            echo "<p style='font-size: 0.9em; color: #888; margin: 0 0 10px;'>Sent on: {$realdate}</p>";
            echo "<div style='color: #ccc; margin-bottom: 8px;'>";
            echo '👍: ' . ($counts['like'] ?? 0) . ' ';
            echo '👎: ' . ($counts['dislike'] ?? 0) . ' ';
            echo '❤️: ' . ($counts['love'] ?? 0) . ' ';
            echo '😲: ' . ($counts['shock'] ?? 0);
            echo "</div>";
            $dropdownId = "dropdown_" . $messageId;
            echo "<div style='display: flex; align-items: center; gap: 10px;'>";
            echo "<a href='../messages/reply.php?id=" . urlencode($messageId) . "'><button>Reply</button></a>";
            echo "<a href='../messages/messhare.php?id=" . urlencode($messageId) . "'><button>Share</button></a>";
            if ($isSaved) {
                echo '<a href="../messages/messages.php?savepost=' . $messageId . '"><button>Unsave Post</button></a>';
            } else {
                echo '<a href="../messages/messages.php?savepost=' . $messageId . '"><button>Save Post</button></a>';
            }
            echo "<div class='dropdown'>
                    <button onclick=\"toggleDropdown('{$dropdownId}')\" class='dropbtn'>React!</button>
                    <div id='{$dropdownId}' class='dropdown-content'>
                        <a href='react.php?id={$messageId}&react=like&gcid=" . $chatid ."'>👍</a>
                        <a href='react.php?id={$messageId}&react=dislike&gcid=" . $chatid ."'>👎</a>
                        <a href='react.php?id={$messageId}&react=love&gcid=" . $chatid ."'>😍</a>
                        <a href='react.php?id={$messageId}&react=shock&gcid=" . $chatid ."'>😰</a>
                    </div>
                  </div>";
            echo "</div><hr style='border-top: 1px #ccc;'>";
            echo "</div>";
        }
    } else {
        echo "No messages.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
</section>

<section id="messages">
    <h1>Send a message</h1>
    <form action="" method="post">
        Message: <input type="text" name="message" required><br>
        <input type="submit" id="trinity" value="chat">
    </form>
    <br>
    <a href="../main.php">Go back to main page</a>
</section>

<script>
function toggleDropdown(id) {
  const el = document.getElementById(id);
  el.classList.toggle("show");
}
window.onclick = function(event) {
  if (!event.target.matches('.dropbtn')) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    for (var i = 0; i < dropdowns.length; i++) {
      dropdowns[i].classList.remove('show');
    }
  }
}
</script>

</body>
</html>
<?php
ob_end_flush(); // send output
?>
