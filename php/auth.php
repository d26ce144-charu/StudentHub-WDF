<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set("session.use_strict_mode", "1");
    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
        "path" => "/",
    ]);
    session_start();
}

require_once __DIR__ . "/config.php";

const SESSION_TIMEOUT_SECONDS = 1800;

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function require_login(bool $json = false): void
{
    if (isset($_SESSION["user_id"])) {
        $lastActivity = (int)($_SESSION["last_activity"] ?? time());
        if (time() - $lastActivity > SESSION_TIMEOUT_SECONDS) {
            $_SESSION = [];
            session_destroy();
            if ($json) {
                json_response(["success" => false, "message" => "Session expired. Please log in again."], 401);
            }
            header("Location: login.html?expired=1");
            exit;
        }
        $_SESSION["last_activity"] = time();
        return;
    }

    if ($json) {
        json_response(["success" => false, "message" => "Please log in to continue."], 401);
    }
    header("Location: login.html");
    exit;
}

function require_admin(bool $json = false): void
{
    require_login($json);
    if (($_SESSION["role"] ?? "student") !== "admin") {
        if ($json) {
            json_response(["success" => false, "message" => "Administrator access is required."], 403);
        }
        http_response_code(403);
        exit("Administrator access is required.");
    }
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function require_csrf(): void
{
    $provided = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? $_POST["csrf_token"] ?? "";
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        json_response(["success" => false, "message" => "Invalid request token. Refresh the page and try again."], 419);
    }
}

function clear_remember_cookie(): void
{
    setcookie("studenthub_remember", "", [
        "expires" => time() - 3600,
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
        "path" => "/",
    ]);
}

function restore_remembered_session(): void
{
    global $pdo;

    if (isset($_SESSION["user_id"]) || empty($_COOKIE["studenthub_remember"])) {
        return;
    }

    $parts = explode(":", $_COOKIE["studenthub_remember"], 2);
    if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{24}$/', $parts[0]) || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
        clear_remember_cookie();
        return;
    }

    [$selector, $validator] = $parts;
    $stmt = $pdo->prepare(
        "SELECT rt.id AS token_id, rt.user_id, rt.validator_hash, u.full_name, u.student_id, u.email, u.role
         FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.selector = :selector AND rt.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([":selector" => $selector]);
    $record = $stmt->fetch();
    if (!$record || !hash_equals($record["validator_hash"], hash("sha256", $validator))) {
        if ($record) {
            $delete = $pdo->prepare("DELETE FROM remember_tokens WHERE id = :id");
            $delete->execute([":id" => $record["token_id"]]);
        }
        clear_remember_cookie();
        return;
    }

    session_regenerate_id(true);
    $_SESSION["user_id"] = (int)$record["user_id"];
    $_SESSION["student_id"] = $record["student_id"];
    $_SESSION["user_name"] = $record["full_name"];
    $_SESSION["user_email"] = $record["email"];
    $_SESSION["role"] = $record["role"];
    $_SESSION["last_activity"] = time();
}

restore_remembered_session();
