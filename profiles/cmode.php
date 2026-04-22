<?php
function getUserPreferredMode($userId, $pdo) {
    $stmt = $pdo->prepare('SELECT preferred_mode FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $preferredMode = $stmt->fetchColumn();

    return $preferredMode;
}

function echoStylesheetTag($preferredMode) {
    $stylesheet = '';

    switch ($preferredMode) {
        case 'dark':
            $stylesheet = '../css/dark-mode.css';
            break;
        case 'blue':
            $stylesheet = '../css/blue.css';
            break;
        case 'nothing':
            $stylesheet = '';
            break;
        case 'opposite':
            $stylesheet = '../css/opposite.css';
            break;
        case 'liberatube':
            $stylesheet = '../css/liberatube.css';
            break;
        case 'nature':
            $stylesheet = '../css/green.css';
            break;
	case 'tecopa':
	    $stylesheet = '../css/tecopa.css';
	    break;
        default:
            $stylesheet = '../css/mainsite.css';
    }

    echo '<link rel="stylesheet" href="' . $stylesheet . '">';
    echo '<script>console.log("Preferred Mode:", ' . json_encode($preferredMode) . ');</script>';
}

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $preferredMode = getUserPreferredMode($userId, $pdo);
} else {
    $preferredMode = 'light';
}

echoStylesheetTag($preferredMode);
?>
