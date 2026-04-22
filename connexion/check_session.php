<?php

session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}


$timeout_duration = 7200; 

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: connexion.php?timeout=1');
    exit();
}


$_SESSION['last_activity'] = time();
?>