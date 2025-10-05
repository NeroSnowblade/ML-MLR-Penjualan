<?php
// Auth check middleware
// Include this at the top of pages that require authentication

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Optional: Check if user is still active
if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['is_active'] != 1) {
        session_destroy();
        header('Location: login.php?error=inactive');
        exit();
    }
}
?>