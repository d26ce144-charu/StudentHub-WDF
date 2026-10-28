<?php

require_once __DIR__ . "/auth.php";

if (!empty($_COOKIE["studenthub_remember"])) {
    $parts = explode(":", $_COOKIE["studenthub_remember"], 2);
    if (isset($parts[0]) && preg_match('/^[a-f0-9]{24}$/', $parts[0])) {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = :selector");
        $stmt->execute([":selector" => $parts[0]]);
    }
}

clear_remember_cookie();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => $params["secure"],
        "httponly" => $params["httponly"],
        "samesite" => "Lax",
    ]);
}
session_destroy();
header("Location: ../login.html");
exit;