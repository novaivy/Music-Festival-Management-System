<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

function isArtist() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'artist');
}

function isFan() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'fan');
}
?>