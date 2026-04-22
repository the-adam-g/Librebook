<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

include '../config.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = htmlspecialchars($_POST["name"], ENT_QUOTES, 'UTF-8');
        $message_text = $_POST["message"];
        if(isset($_POST['nsfw'])){
            $nsfw = 1;
        } else {
            $nsfw = 0;
        }
        $userId = $_SESSION['user_id'];

        if (empty($name) || empty($message_text)) {
            echo "Sender name and message are required!";
        } else {
            $username = $_SESSION['username'];
            $stmt = $pdo->prepare("SELECT kids FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $kids_mode = $stmt->fetchColumn();

            if ($kids_mode == 'on') {
                $forbiddenWords = json_decode(file_get_contents('bad-words.json'), true);
                $lowercase_message_text = strtolower($message_text);
                foreach ($forbiddenWords as $word) {
                    if (strpos($lowercase_message_text, strtolower($word)) !== false) {
                        echo "Warning! Your message contained language not allowed in kids mode! You can bypass this by disabling it in settings";
                        return;
                    }
                }
            }

            if (isset($_SESSION['replyto'])) {
                $message_text = $_SESSION['replyto'] . $message_text;
            }

            $stmt = $pdo->prepare("INSERT INTO messages (`userid`, `name`, `message`, `timestamp`, `nsfw`) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)");
            $stmt->execute([$userId, $name, $message_text, $nsfw]);
            echo "Message sent successfully!";
            unset($_SESSION['replyto']);
            header("Location: ../main.php");
            exit();
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
unset($_SESSION['replyto']);
?>
