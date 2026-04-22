<?php
session_start();
$error_message = $_SESSION['error_message'] ?? 'An unknown error occurred during registration.';
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librebook - Registration Error</title>
    <link rel="stylesheet" href="/css/mainsite.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 30px;
            background-color: #f4f4f4;
        }
        #head {
            margin-bottom: 30px;
        }
        #sendamess, #messages {
            font-size: 20px;
        }
        #messages {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #error-text {
            color: #d00;
            font-weight: bold;
            margin-top: 15px;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            color: #0073e6;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <section id="head">
        <img src="/images/librebook1.png" style="max-width: 100%; height: auto; width: 125px; float: right;">
        <h1 id="headl">Librebook</h1>
    </section>

    <section id="sendamess">
        <section id="messages">
            <h1>Whoops! Something went wrong.</h1>
            <p>An error occurred during your registration.</p>
            <p id="error-text"><?= htmlspecialchars($error_message) ?></p>
            <a href="/registration/register.html">Please try again</a>
        </section>
    </section>

    <script src="script.js"></script>
</body>
</html>
