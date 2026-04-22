<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config.php';
include 'cmode.php';
$username = $_SESSION['username'];
$_SESSION['mostrecent'] = isset($_GET['id']) ? $_GET['id'] : '';
$userId = $_SESSION['user_id'] ?? null;
$sui = isset($_SESSION['sui']) ? $_SESSION['sui'] : null;
$oname = isset($_SESSION['oname']) ? $_SESSION['oname'] : null;
$omessage = isset($_SESSION['omessage']) ? $_SESSION['omessage'] : null;
$otime = isset($_SESSION['otimestamp']) ? $_SESSION['otimestamp'] : null;
$oid = isset($_SESSION['oid']) ? $_SESSION['oid'] : null;
function extractVideoId($url) {
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'], $query);
    return isset($query['v']) ? $query['v'] : null;
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

function convertHashtagsToLinks($message) {
    $pattern = '/#(\w+)/';
    $messageWithLinks = preg_replace($pattern, '<a href="../messages/hashtag.php?tag=$1">#$1</a>', $message);
    return $messageWithLinks;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
<style>
    #blading {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 4px solid white;
        position: absolute;
        bottom: -75px;
        left: 20px;
        z-index: 2;
        background-color: white;
    }

    .profile-header {
        position: relative;
        max-width: 900px;
        margin: 0 auto 90px auto;
    }

    #banner {
        width: 100% !important;
        max-width: 900px !important;
        max-height: 450px !important;
        height: auto !important;
        display: block;
    }
</style>
</head>
<body>
    <section id="head">
        <img src="../images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>
    <br>
    <div id="helloworld" style="float: left;">
        <button onclick='location="../main.php"'>Take me back!</button>
        <?php
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $stmt = $pdo->prepare("SELECT username, bio, pfp, PROCOLOUR, bimg FROM profiles WHERE username LIKE :searchTerm LIMIT 1");
        $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
        $foundProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
    </div>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($searchTerm)) {
            echo '<section id="messages">';
            echo "Please enter a search term.";
            echo '</section>';
        } else {
            $_SESSION['searchTerm'] = $searchTerm;

            $stmt = $pdo->prepare("SELECT username, bio, pfp, PROCOLOUR, bimg FROM profiles WHERE username LIKE :searchTerm LIMIT 1");
            $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
            $foundProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            $rawName = $foundProfile['username'];
            $vname = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');

            $vStmt = $pdo->prepare("SELECT verified FROM users WHERE username = ?");
            $vStmt->execute([$rawName]);
            $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);

            $isverif = $vRow["verified"] ?? 0;

            $fullname = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');
            
            if ($isverif) {
                $fullname .= ' <img src="/images/verified2.svg" alt="Verified" style="vertical-align: middle; max-width: 18px; max-height: 18px;">';
            }


            if ($foundProfile) {
                echo '<meta property="og:title" content="' . htmlspecialchars($foundProfile['username']) . '">';
                echo '<meta property="og:description" content="' . htmlspecialchars($foundProfile['bio']) . '">';
                echo '<meta property="og:image" content="' . htmlspecialchars($foundProfile['pfp']) . '">';
                echo '<meta property="og:url" content="' . SITEURL . ltrim($_SERVER['REQUEST_URI'], '/') . '">';
                echo '<style> body { background-image: none !important; background-color: ' . htmlspecialchars($foundProfile['PROCOLOUR']) . ' !important; }</style>';
                echo '<meta property="og:type" content="profile">';
                echo '<section id="messages" style="flex-grow: 1;">';
                echo '<div class="profile-header">';
                echo '<img id="banner" src="'. htmlspecialchars($foundProfile['bimg']) . '" alt="Banner">';
                echo '<img src="' . htmlspecialchars($foundProfile['pfp']) . '" alt="Profile Picture" id="blading">';
                echo '</div>';
                echo '<h1>Username: ' . $fullname . '</h1>';
                echo '<p>Bio: ' . htmlspecialchars($foundProfile['bio']) . '</p>';

                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$rawName]);
                $resultofsearch = $stmt->fetch(PDO::FETCH_ASSOC);
                $blockID = $resultofsearch['id'] ?? null;
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$foundProfile['username']]);
                $viewedUserId = $stmt->fetchColumn();
                if ($viewedUserId === false) {
                    echo "<p style='color:red;'>Profile not found.</p>";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM following WHERE followuser_id = ?");
                    $stmt->execute([$viewedUserId]);
                    $followersCount = (int)$stmt->fetchColumn();
                    echo "<p>" . htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8') . " has <span style='color:#1877f2; font-weight: bold;'>" . $followersCount . "</span> follower(s).</p>";
                    $isFollowing = false;
                    if (!empty($userId)) {
                        $stmt = $pdo->prepare("SELECT 1 FROM following WHERE followuser_id = ? AND followinguser_id = ? LIMIT 1");
                        $stmt->execute([$viewedUserId, $userId]);
                        $isFollowing = (bool)$stmt->fetchColumn();
                    }
                    if ($isFollowing) {
                        echo "<p>You follow this user!</p>";
                    } else {
                        echo "<p>You do not follow this user!</p>";
                    }
                }
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM blocked WHERE blockedID = ? AND userID = ?');
                $stmt->execute([$blockID, $userId]);
                $count = $stmt->fetchColumn();
                if ($count == 1) {
                    echo "<p style='color:red;'>You have blocked this user.</p>";
                } else {
                }
                echo '<form method="post" action="block.php">';
                echo '<input type="submit" value="Block/Unblock" />';
                echo '</form>';
                echo '<form method="post" action="follow.php">';
                echo '<input type="submit" value="Follow/Unfollow" />';
                echo '</form>';
                echo '</section>';

                echo '<section id="messages">';
                try {
                    $messageId = isset($_GET['id']) ? intval($_GET['id']) : null;

                    $stmt = $pdo->prepare("SELECT `id`, `name`, `message`, `timestamp`, `nsfw` FROM messages WHERE name = :name ORDER BY `timestamp` DESC");
                    $stmt->bindParam(':name', $foundProfile['username'], PDO::PARAM_STR);
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

                            $vStmt = $pdo->prepare("SELECT verified FROM users WHERE username = ?");
                            $vStmt->execute([$name]);
                            $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
                            $userIsVerified = $vRow["verified"] ?? 0;

                            $verifiedBadge = '';
                            if ($userIsVerified) {
                                $verifiedBadge = ' <img src="/images/verified2.svg" alt="Verified" style="vertical-align: middle; max-width: 18px; max-height: 18px;">';
                            }

                            $nameLink = '<a href="../profiles/rprofiles.php?search=' . urlencode($name) . '">' . $name . '</a>' . $verifiedBadge;


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
                            if ($isNSFW) {
                                echo "<p style='color:red;'>NSFW</p>";
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
            } else {
                echo '<section id="messages">';
                echo "<h1 style='text-align: center'>User does not exist or their account has been deleted.</h1>";
                echo '<img src="../images/notfound.png" alt="image not found as well. its not your lucky day" style="display: block; margin-left: auto; margin-right: auto;">';
                echo '</section>';
            }
        }
    }
    ?>
</body>
</html>
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