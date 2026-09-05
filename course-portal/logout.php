<?php

error_reporting(E_ALL ^ E_NOTICE);

// Resume the existing session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// 1. Clear all session variables
session_unset();

// 2. Destroy the server-side session data
session_destroy();

// 3. Expire the session cookie on the client immediately
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,  // set expiry to the past so the browser deletes it
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Redirect to login page
header("Location: login.php?loggedout=1");
exit();
