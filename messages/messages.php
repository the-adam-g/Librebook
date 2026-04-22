<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include '../config.php';
session_start();

if (isset($_GET['savepost'])) {
    savepost($_GET['savepost']);
}

$userId = $_SESSION['user_id'] ?? null;
$pagenum = $_SESSION['page'];
$kmode = $_SESSION['kmode'] ?? 'off';

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
            $replacement = "<a href='messages/spmessages.php/?id=$numbers'>Reply to</a>";
            $string = substr_replace($string, $replacement, $symbolPosition, $semicolonPosition + 1);
        }
    }
    return $string;
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

        header("Location: ../main.php");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}



function convertHashtagsToLinks($message) {
    $pattern = '/#(\w+)/';
    return preg_replace($pattern, '<a href="messages/hashtag.php?tag=$1">#$1</a>', $message);
}

function convertNameToLink($name) {
    return '<a href="profiles/rprofiles.php?search=' . urlencode($name) . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
}

try {
    $messagenum = ($pagenum - 1) * $offsetnum;
    $messageId = isset($_GET['id']) ? intval($_GET['id']) : null;

    if ($messageId) {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp`, `nsfw` FROM messages WHERE id = :id");
        $stmt->bindParam(':id', $messageId, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp`, `nsfw` FROM messages ORDER BY `timestamp` DESC LIMIT 10 OFFSET :offset");
        $stmt->bindParam(':offset', $messagenum, PDO::PARAM_INT);
    }

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        foreach ($result as $row) {
            $id = $row["id"];
            $counts = [];

            $reactionStmt = $pdo->prepare("SELECT reaction, COUNT(*) AS total FROM reactions WHERE messageid = ? GROUP BY reaction");
            $reactionStmt->execute([$id]);
            while ($r = $reactionStmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[$r['reaction']] = $r['total'];
            }

            $rawName = $row["name"];
            $vname = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');

            $vStmt = $pdo->prepare("SELECT verified FROM users WHERE username = ?");
            $vStmt->execute([$rawName]);
            $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);

            $isverif = $vRow["verified"] ?? 0;

            if ($isverif) {
                $vname .= ' <img src=images/verified2.svg alt="Verified" style="vertical-align: middle; max-width: 18px; max-height: 18px;">';
            }

            $nameLink = '<a href="profiles/rprofiles.php?search=' . urlencode($rawName) . '">' . $vname . '</a>';

            $message = htmlspecialchars($row["message"], ENT_QUOTES, 'UTF-8');
            $timestamp = $row["timestamp"];
            $isNSFW = $row["nsfw"] ?? 0;

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

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$rawName]);
            $resultofsearch = $stmt->fetch(PDO::FETCH_ASSOC);
            $blockID = $resultofsearch['id'] ?? null;
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM blocked WHERE blockedID = ? AND userID = ?');
            $stmt->execute([$blockID, $userId]);
            $count = $stmt->fetchColumn();
            if ($count == 1) {
                $message = '<p style="color: red;">[YOU HAVE BLOCKED THIS USER]</p>';
            } else {
            }

            $message = extractID($message);
            $message = convertHashtagsToLinks($message);

            $realdate = date("l M j, Y h:i:s A", strtotime($timestamp));
            echo "<div style='border-radius: 8px; margin-bottom: 12px; color: #ccc; font-family: sans-serif;'>";

            if (filter_var($message, FILTER_VALIDATE_URL) &&
                (str_contains($message, '.jpg') || str_contains($message, '.jpeg') || str_contains($message, '.png') || str_contains($message, '.webp'))) {
                echo "<p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><img src='{$message}' alt='Image' style='max-width: auto; height: auto; max-height: auto;'></p>";
            } elseif (str_contains($message, 'https://ltbeta.epicsite.xyz/videodata/non-hls.php?id=')) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://ltbeta.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "<p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><video controls loading='lazy' poster='http://i.ytimg.com/vi/{$videoId}/mqdefault.jpg'><source src='{$videoUrl}' type='video/mp4'></video></p>";
                }
            } elseif (str_contains($message, 'https://www.youtube.com/watch?v=') || str_contains($message, 'https://lt.epicsite.xyz/watch/?v=')) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://lt.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
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

            $dropdownId = "dropdown_" . $id;
            echo "<div style='display: flex; align-items: center; gap: 10px;'>";
            echo "<a href='messages/reply.php?id=" . urlencode($id) . "' style='color: #4aa3ff; text-decoration: none;'><button>Reply</button></a>";
            echo "<a href='messages/messhare.php?id=" . urlencode($id) . "' style='color: #4aa3ff; text-decoration: none;'><button>Share</button></a>";
            $savedPostsString = '';
            if (isset($_SESSION['username'])) {
                $stmt = $pdo->prepare("SELECT saved FROM users WHERE username = ?");
                $stmt->execute([$_SESSION['username']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $savedPostsString = $row['saved'] ?? '';
            }
            $savedPostsArray = array_filter(array_map('trim', explode(',', $savedPostsString)));
            $isSaved = in_array($id, $savedPostsArray);
            if ($isSaved) {
                echo '<a href="messages/messages.php?savepost=' . $id . '"><button>Unsave Post</button></a>';
            } else {
                echo '<a href="messages/messages.php?savepost=' . $id . '"><button>Save Post</button></a>';
            }
            echo "<div class='dropdown'>
                    <button onclick=\"toggleDropdown('{$dropdownId}')\" class='dropbtn'>React!</button>
                    <div id='{$dropdownId}' class='dropdown-content'>
                        <a href='messages/react.php?id={$id}&react=like'>👍</a>
                        <a href='messages/react.php?id={$id}&react=dislike'>👎</a>
                        <a href='messages/react.php?id={$id}&react=love'>😍</a>
                        <a href='messages/react.php?id={$id}&react=shock'>😰</a>
                    </div>
                  </div>";
            echo "</div><hr style='border-top: 1px #ccc;'>";
            echo "</div>";
        }
    } else {
        echo "No messages.";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
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
