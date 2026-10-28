<?php

require_once __DIR__ . "/auth.php";

header("Content-Type: application/json; charset=utf-8");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["success" => false, "message" => "Invalid request method."], 405);
}

$email = isset($_POST["email"]) && is_string($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";
$password = $_POST["password"] ?? "";
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === "") {
    json_response(["success" => false, "message" => "Enter a valid email address and password."], 422);
}

$stmt = $pdo->prepare(
    "SELECT id, full_name, student_id, email, course, study_year, semester, password, role
     FROM users WHERE email = :email LIMIT 1"
);
$stmt->execute([":email" => $email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user["password"])) {
    json_response(["success" => false, "message" => "Invalid email or password."], 401);
}

if (password_needs_rehash($user["password"], PASSWORD_DEFAULT)) {
    $update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $update->execute([
        ":password" => password_hash($password, PASSWORD_DEFAULT),
        ":id" => $user["id"],
    ]);
}

session_regenerate_id(true);
$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["student_id"] = $user["student_id"];
$_SESSION["user_name"] = $user["full_name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["role"] = $user["role"];
$_SESSION["last_activity"] = time();

if (isset($_POST["remember"])) {
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires = (new DateTimeImmutable("+30 days"))->format("Y-m-d H:i:s");
    $save = $pdo->prepare(
        "INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (:user_id, :selector, :validator_hash, :expires_at)"
    );
    $save->execute([
        ":user_id" => $user["id"],
        ":selector" => $selector,
        ":validator_hash" => hash("sha256", $validator),
        ":expires_at" => $expires,
    ]);
    setcookie("studenthub_remember", $selector . ":" . $validator, [
        "expires" => time() + 30 * 24 * 60 * 60,
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
        "path" => "/",
    ]);
}

json_response([
    "success" => true,
    "message" => "Login successful.",
    "redirect" => $user["role"] === "admin" ? "admin.php" : "profile.php",
    "user" => [
        "id" => (int)$user["id"],
        "full_name" => $user["full_name"],
        "student_id" => $user["student_id"],
        "email" => $user["email"],
        "course" => $user["course"],
        "semester" => (int)$user["semester"],
        "role" => $user["role"],
    ],
]);
