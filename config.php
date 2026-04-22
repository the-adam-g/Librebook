<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = '';
$db_name = '';
$db_user = '';
$db_password = '';
$sacredwork = ''; // For a future unreleased update regarding verifying accounts 
define('SITEURL', 'https://librebook.co.uk/'); // replace with your url

$offsetnum = 10; // the number of messages loaded per page

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>