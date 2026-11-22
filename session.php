<?php
if (session_status() === PHP_SESSION_NONE){
    $lifetime = 60 * 60 * 24 * 30;

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.gc_maxlifetime', (string)$lifetime);
    session_start();
}

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_logged_in(): bool {
    return current_user_id() !== null;
}
?>