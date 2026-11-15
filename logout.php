<?php

require_once "includes/auth.php";

// Logout hanya menerima permintaan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

verifyCsrf();

// Hapus seluruh data session
$_SESSION = [];

// Hapus cookie session
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}

// Hancurkan session
session_destroy();

header("Location: login.php?logout=1");
exit;
