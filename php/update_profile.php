<?php

require_once __DIR__ . "/auth.php";
require_login(true);


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    json_response([
        "success" => false,
        "message" => "Invalid request method."
    ], 405);
}
require_csrf();


$field = static fn(string $key): string => isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : "";
$fullName = $field("fullName");
$email = $field("email");
$course = $field("course");
$semester = (int)$field("semester");


if (
    !preg_match("/^[\p{L}][\p{L} .'-]{1,119}$/u", $fullName) ||
    $email === "" ||
    strlen($email) > 190 ||
    $course === "" || strlen($course) > 120 ||
    $semester < 1 ||
    $semester > 8
) {

    json_response([
        "success" => false,
        "message" => "Please enter valid profile information."
    ], 422);
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    json_response([
        "success" => false,
        "message" => "Invalid email address."
    ], 422);
}


/* Check whether email belongs to another user */

try {
    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE email = :email AND id != :id"
    );
    $stmt->execute([":email" => $email, ":id" => $_SESSION["user_id"]]);
    if ($stmt->fetch()) {
        json_response(["success" => false, "message" => "That email address is already in use."], 409);
    }

    $stmt = $pdo->prepare(
        "UPDATE users SET full_name = :full_name, email = :email, course = :course, semester = :semester WHERE id = :id"
    );
    $stmt->execute([
        ":full_name" => $fullName,
        ":email" => $email,
        ":course" => $course,
        ":semester" => $semester,
        ":id" => $_SESSION["user_id"],
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        json_response(["success" => false, "message" => "That email address is already in use."], 409);
    }
    error_log("StudentHub profile update failed: " . $e->getMessage());
    json_response(["success" => false, "message" => "Profile update failed."], 500);
}


/* Update session */

$_SESSION["user_name"] = $fullName;
$_SESSION["user_email"] = $email;


json_response([
    "success" => true,
    "message" => "Profile updated successfully."
]);