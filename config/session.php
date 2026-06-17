<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['id_user']) && isset($_SESSION['role']);
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /job-board/auth/login.php");
        exit();
    }
}

// Redirect if not specific role
function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header("Location: /job-board/dashboard.php");
        exit();
    }
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header("Location: /job-board/auth/login.php");
    exit();
}
?>