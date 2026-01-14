<?php
// functions.php

// sanitize output
function esc($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// require login for roles
function require_role($role) {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        redirect('login.php');
    }
}

// flash messages
function flash_get($key) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
function flash_set($key, $message) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION[$key] = $message;
}
?>