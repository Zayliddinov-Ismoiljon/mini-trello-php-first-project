<?php
session_start();

// Sessiyadagi barcha ma'lumotlarni tozalash
$_SESSION = [];

// Brauzerdagi sessiya cookie sini ham o'chirish
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Serverdagi sessiya faylini o'chirish
session_destroy();

header('Location: index.php');
exit;