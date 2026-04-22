<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = 'localhost';
$db_name = 'librebooknext';
$db_user = 'root';
$db_password = '';
$sacredwork = ''; // For a future unreleased update regarding verifying accounts 

$offsetnum = 10; // the number of messages loaded per page

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

/*

CREATE TABLE messages (
  `id`          BIGINT AUTO_INCREMENT PRIMARY KEY,   -- was INT PRIMARY KEY
  `team_id`     BIGINT NOT NULL,
  `user_id`   BIGINT NOT NULL,                     -- was user VARCHAR(255)
  `body`        TEXT NOT NULL,                       -- was message VARCHAR(255)
  `created`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- was timestamp
  INDEX(team_id, created)                       -- better than team_id alone
) ENGINE=InnoDB;


CREATE TABLE teams (
  `id`         BIGINT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB;


CREATE TABLE users (
  `id`            BIGINT AUTO_INCREMENT PRIMARY KEY,
  `name`  VARCHAR(255) NULL,
  `password` varchar(255) NOT NULL,
  `created`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB;



*/
?>