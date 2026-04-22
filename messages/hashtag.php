<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
} else {
    header('Location: ../index.php');
    exit();
}
?>
<?php
// so for some stupid reason the code always breaks unless its in its own 'container' of sorts dont know why and probably wont fix - Adam Gillion
include '../cmode.php'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>librebook</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <br>
    <div id="helloworld" style="position: sticky; align-self: flex-start; float: left;">
        <?php
        echo 'Welcome back ' . htmlspecialchars($username) . '!';
        ?>
        <p></p>
        <a href="../deleteyou.php">Delete your account</a><a href="../settings.php" style="float: right;">Go to Settings</a>
        <p></p>
        <a href="../logout.php">Logout</a><a href="../profiles/sprofile.php" style="float: right;">See my profile</a>
        <p></p>
        <a href="../main.php">Take me back!</a>
    </div>
</body>
<?php
include '../config.php';
$kmode = $_SESSION['kmode'] ?? 'off';

function extractVideoId($url) {
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'] ?? '', $query);
    return $query['v'] ?? null;
}

function convertHashtagsToLinks($message) {
    $pattern = '/#(\w+)/';
    return preg_replace($pattern, '<a href="../messages/hashtag.php?tag=$1">#$1</a>', $message);
}

function convertNameToLink($name) {
    return '<a href="../profiles/rprofiles.php?search=' . urlencode($name) . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a>';
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

try {
    $hashtag = isset($_GET['tag']) ? $_GET['tag'] : '';


    $query = "SELECT `id`, `name`, `message`, `timestamp`, `nsfw`
              FROM messages
              WHERE `message` LIKE :hashtag
              ORDER BY `timestamp` DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':hashtag', "%#$hashtag%", PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($result) {
        echo "<section id='messages'>";
        echo "<h1 style='text-align: center;'>Librebook hashtag: #$hashtag</h1>";
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
                $vname .= ' <img src=/images/verified2.svg alt="Verified" style="vertical-align: middle; max-width: 18px; max-height: 18px;">';
            }

            $nameLink = '<a href="../profiles/rprofiles.php?search=' . urlencode($rawName) . '">' . $vname . '</a>';

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
            echo "<a href='../messages/reply.php?id=" . urlencode($id) . "' style='color: #4aa3ff; text-decoration: none;'><button>Reply</button></a>";
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
        echo "</section>";
    } else {
        echo "No messages with this hashtag.";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
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
