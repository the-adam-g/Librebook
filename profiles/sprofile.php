<?php
session_start();
include '../config.php';
include 'cmode.php';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $foundProfile = null;

    $stmt = $pdo->prepare("SELECT username, pfp, bio, bimg FROM profiles WHERE username = ?");
    $stmt->execute([$username]);
    $foundProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$foundProfile) {
        echo '<p>User profile not found</p>';
    }
} else {
    header('Location: ../login.html');
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_profile'])) {
        $newPfp = isset($_POST['new_pfp']) ? $_POST['new_pfp'] : $foundProfile['pfp'];
        $newBimg = isset($_POST['new_bimg']) ? $_POST['new_bimg'] : $foundProfile['bimg'];
        $newBio = isset($_POST['new_bio']) ? htmlspecialchars($_POST['new_bio']) : $foundProfile['bio'];
        $procolour = isset($_POST['procolour']) ? htmlspecialchars($_POST['procolour']) : 'libreblue';
        
        $stmt = $pdo->prepare("UPDATE profiles SET pfp = ?, bio = ?, PROCOLOUR = ?, bimg = ? WHERE username = ?");
        $stmt->execute([$newPfp, $newBio, $procolour, $newBimg, $username]);

        if ($stmt->rowCount() > 0) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = '<p style="color: red;">Error updating profile</p>';
        }
    }
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = '<p style="color: red;">All password fields are required.</p>';
        } elseif ($newPassword !== $confirmPassword) {
            $message = '<p style="color: red;">New passwords do not match.</p>';
        } elseif (strlen($newPassword) < 6) {
            $message = '<p style="color: red;">New password must be at least 6 characters long.</p>';
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($currentPassword, $user['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$hashedPassword, $username]);
                
                if ($stmt->rowCount() > 0) {
                    $message = '<p style="color: green;">Password updated successfully!</p>';
                } else {
                    $message = '<p style="color: red;">Error updating password.</p>';
                }
            } else {
                $message = '<p style="color: red;">Current password is incorrect.</p>';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
</head>
<style>
    img {
        border-radius: 50%;
        width: 150px;
        height: 150px;
    }
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
        border-radius: 0%;
    }
    
    .form-section {
        margin: 20px 0;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .form-section h2 {
        margin-top: 0;
    }
    
    .password-form input[type="password"] {
        width: 300px;
        padding: 8px;
        margin: 5px 0;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    
    .password-form label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }
</style>
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
    <section id="sendamess">
        <section id="messages">
            <h1>My Profile</h1>
            
            <?php echo $message; ?>
            
            <div class="profile-header">
            <img id="banner" src="<?php echo htmlspecialchars($foundProfile['bimg']);  ?>" alt="Banner">
            <img src="<?php echo $foundProfile['pfp']; ?>" alt="Profile Picture" id="blading">
            </div>
            <h1>Username: <?php echo $foundProfile['username']; ?></h1>
            <p>Bio: <?php echo $foundProfile['bio']; ?></p>
            
            <div class="form-section">
                <h2>Edit Profile</h2>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <label for="new_pfp">New Profile Picture URL:</label><br>
                    <input type="text" name="new_pfp" id="new_pfp" placeholder="Enter new profile picture URL" value="<?php echo $foundProfile['pfp']; ?>">
                    <br>
                    <label for="new_bio">New Bio:</label>
                    <br>
                    <textarea name="new_bio" id="new_bio" placeholder="Enter new bio" rows="4" cols="50"><?php echo $foundProfile['bio']; ?></textarea>
                    <br>
                    <label for="new_bimg">New Profile Banner URL:</label><br>
                    <input type="text" name="new_bimg" id="new_bimg" placeholder="Enter new profile banner URL" value="<?php echo $foundProfile['bimg']; ?>">
                    <br>
                    <button type="submit" name="update_profile">Update Profile</button>
                </form>
            </div>
            
            <div class="form-section">
                <h2>Change Password</h2>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="password-form">
                    <label for="current_password">Current Password:</label>
                    <input type="password" name="current_password" id="current_password" required>
                    
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password" required minlength="6">
                    
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                    
                    <br><br>
                    <button type="submit" name="change_password">Change Password</button>
                </form>
            </div>
            
            <div class="form-section">
                <h2>Profile Colour</h2>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <label for="procolour">Select your desired profile color:</label>
                    <input type="color" id="procolour" name="procolour" value="#ff0000"><br><br>
                    <button type="submit" name="update_profile">Update Profile Colour</button>
                </form>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="procolour" value="None">
                    <button type="submit" name="update_profile">Reset Profile Colour</button>
                </form>
            </div>
        </section>
        <br></br>
    </section>
</body>
</html>