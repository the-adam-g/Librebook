<?php
include '../config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../profiles/cmode.php';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
} else {
    header('Location: ../index.php');
    exit();
}

$date = isset($_GET['date']) ? trim($_GET['date']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo'<title>Messages from ' . $date . '</title>'; ?>
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
    <section id="frlist" style="float: right;">
        <h1>Your friends</h1>
        <?php
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $stmt = $pdo->prepare("SELECT username, bio, pfp, PROCOLOUR, bimg FROM profiles WHERE username LIKE :searchTerm LIMIT 1");
        $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
        $foundProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT following FROM users WHERE username = ?");
        $stmt->execute([$foundProfile['username']]);
        $followers = $stmt->fetchColumn();

        if ($followers !== null) {
            $followersList = explode(',', $followers);
            echo "Followers for " . htmlspecialchars($foundProfile['username']) . ": <br>";
            foreach ($followersList as $follower) {
                echo htmlspecialchars($follower) . '<br>';
            }
        } else {
            echo "User " . htmlspecialchars($foundProfile['username']) . " has no followers.";
        }
        ?>
    </section>
<section id="messages" style="flex-grow: 1;">
<?php 
echo'<h1>Messages from ' . $date . '</h1>'; 
echo '<hr>';

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

function convertNameToLink($name) {
    return '<a href="../profiles/rprofiles.php?search=' . urlencode($name) . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
}

try {;
    $messageId = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!empty($messageId) && !empty($date)) {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp` FROM messages WHERE `id` = :id AND `timestamp` LIKE :da");
        $stmt->bindParam(':id', $messageId, PDO::PARAM_INT);
        $stmt->bindValue(':da', '%' . $date . '%', PDO::PARAM_STR);
    } elseif (!empty($messageId)) {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp` FROM messages WHERE `id` = :id");
        $stmt->bindParam(':id', $messageId, PDO::PARAM_INT);
    } elseif (!empty($date)) {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp` FROM messages WHERE `timestamp` LIKE :da");
        $stmt->bindValue(':da', '%' . $date . '%', PDO::PARAM_STR);
    } else {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp` FROM messages");
    }

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if ($result) {
        foreach ($result as $row) {
            $id = $row["id"];
            $counts = [];
            $query = "SELECT reaction, COUNT(*) AS total FROM reactions WHERE messageid = ? GROUP BY reaction";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[$r['reaction']] = $r['total'];
            }
            $name = htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8');
            $nameLink = convertNameToLink($name);
            $message = htmlspecialchars($row["message"], ENT_QUOTES, 'UTF-8');
            $timestamp = $row["timestamp"];
            $message = extractID($message);
            $message = convertHashtagsToLinks($message);
            $kmode = $_SESSION['kmode'] ?? 'off';
            if ($kmode == 'on') {
                $forbiddenWords = json_decode(file_get_contents('bad-words.json'), true);
                $lowercase_message_text = strtolower($message);
                foreach ($forbiddenWords as $word) {
                    if (strpos($lowercase_message_text, strtolower($word)) !== false) {
                        $message = str_ireplace($word, '[CENSORED BY KIDS MODE] ', $message);
                    }
                }
            }
            $realdate = date("l M j, Y h:i:s A", strtotime($timestamp));
            echo "<div style='border-radius: 8px; margin-bottom: 12px; color: #ccc; font-family: sans-serif;'>";
            if (filter_var($message, FILTER_VALIDATE_URL) && 
                (strpos($message, '.jpg') !== false || strpos($message, '.jpeg') !== false || strpos($message, '.png') !== false || strpos($message, '.webp') !== false)) {
                echo "  <p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><img src='{$message}' alt='Image' style='max-width: auto; height: auto; max-height: auto;'></p>";
            }
            elseif (strpos($message, 'https://ltbeta.epicsite.xyz/videodata/non-hls.php?id=') !== false) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://ltbeta.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "  <p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><video controls loading='lazy' poster='http://i.ytimg.com/vi/{$videoId}/mqdefault.jpg'><source src='{$videoUrl}' type='video/mp4'></video></p>";
                }
            }
            elseif (strpos($message, 'https://www.youtube.com/watch?v=') !== false || strpos($message, 'https://lt.epicsite.xyz/watch/?v=') !== false) {
                $videoId = extractVideoId($message);
                if ($videoId) {
                    $videoUrl = "https://lt.epicsite.xyz/videodata/non-hls.php?id={$videoId}&dl=dl&itag=18";
                    echo "  <p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b><br><video controls loading='lazy' poster='http://i.ytimg.com/vi/{$videoId}/mqdefault.jpg'><source src='{$videoUrl}' type='video/mp4'></video></p>";
                }
            }
            else {
                echo "  <p style='font-size: 18px; margin: 0 0 8px;'><b style='color: #4aa3ff;'>{$nameLink}:</b> {$message}</p>";
            }
            echo "  <p style='font-size: 0.9em; color: #888; margin: 0 0 10px;'>Sent on: {$realdate}</p>";
            echo "<div style='color: #ccc; margin-bottom: 8px;'>";
            echo '👍: ' . ($counts['like'] ?? 0) . ' ';
            echo '👎: ' . ($counts['dislike'] ?? 0) . ' ';
            echo '❤️: ' . ($counts['love'] ?? 0) . ' ';
            echo '😲: ' . ($counts['shock'] ?? 0);
            echo "</div>";
            echo "<div style='display: flex; align-items: center; gap: 10px;'>";
            $dropdownId = "dropdown_" . $id;
            echo "<a href='../messages/reply.php?id=" . urlencode($id) . "'  style='color: #4aa3ff; text-decoration: none;'><button>Reply</button></a>";
            echo '<div class="dropdown">
                  <button onclick="toggleDropdown(\'' . $dropdownId . '\')" class="dropbtn">React!</button>
                  <div id="' . $dropdownId . '" class="dropdown-content">
                    <a href="messages/react.php?id=' . $id . '&react=like">👍</a>
                    <a href="messages/react.php?id=' . $id . '&react=dislike">👎</a>
                    <a href="messages/react.php?id=' . $id . '&react=love">😍</a>
                    <a href="messages/react.php?id=' . $id . '&react=shock">😰</a>
                  </div>
                </div>';
            echo "</div>";
            echo "<hr style='border-top: 1px #ccc;'>";
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
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
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