
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config.php';
session_start();

$loginuser   = $_SESSION['username'] ?? null;
$searchusern = $_SESSION['searchTerm'] ?? null;

if (empty($loginuser)) {
    header("Location: notlogin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt1 = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt1->execute([$loginuser]);
    $followinguser_id = $stmt1->fetchColumn();
    $stmt2 = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt2->execute([$searchusern]);
    $followuser_id = $stmt2->fetchColumn();
    if (!$followinguser_id || !$followuser_id) {
        header("Location: rprofiles.php?search=" . urlencode($searchusern) . "&err=unknown_user");
        exit();
    }
    $check = $pdo->prepare("SELECT 1 FROM following WHERE followuser_id = ? AND followinguser_id = ? LIMIT 1");
    $check->execute([$followuser_id, $followinguser_id]);
    $exists = $check->fetchColumn();
    if (!$exists) {
        $stmt3 = $pdo->prepare("INSERT INTO following (followuser_id, followinguser_id, followingname, timestamp) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt3->execute([$followuser_id, $followinguser_id, $loginuser]);
    } else {
        $stmt4 = $pdo->prepare("DELETE FROM following WHERE followuser_id = ? AND followinguser_id = ?");
        $stmt4->execute([$followuser_id, $followinguser_id]);
    }
}
header("Location: rprofiles.php?search=" . urlencode($searchusern));
exit();
