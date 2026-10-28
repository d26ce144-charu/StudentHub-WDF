<?php

require_once __DIR__ . "/auth.php";
require_login(true);

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        student_id,
        email,
        course,
        semester
     FROM users
     WHERE id = :id
     LIMIT 1"
);

$stmt->execute([
    ":id" => $_SESSION["user_id"]
]);

$user = $stmt->fetch();


if (!$user) {
    json_response([
        "success" => false,
        "message" => "User not found."
    ], 404);
}


json_response([
    "success" => true,
    "user" => $user,
    "csrf_token" => csrf_token(),
]);