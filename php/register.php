<?php

require_once __DIR__ . "/auth.php";

header("Content-Type: application/json; charset=utf-8");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["success" => false, "message" => "Invalid request method."], 405);
}

$field = static fn(string $key): string => isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : "";
$fullName = $field("fullname");
$studentId = $field("studentid");
$email = strtolower($field("email"));
$mobile = $field("mobile");
$course = $field("course");
$year = filter_var($field("year"), FILTER_VALIDATE_INT);
$semester = filter_var($field("semester"), FILTER_VALIDATE_INT);
$gender = $field("gender");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm-password"] ?? "";
$allowedGenders = ["female", "male", "other", "prefer-not-to-say"];

if (
    !preg_match("/^[\p{L}][\p{L} .'-]{1,119}$/u", $fullName) ||
    !preg_match("/^[A-Za-z0-9-]{2,40}$/", $studentId) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 190 ||
    !preg_match("/^[6-9][0-9]{9}$/", $mobile) ||
    !preg_match("/^[\p{L}][\p{L}0-9 &.'-]{1,119}$/u", $course) ||
    $year === false || $year < 1 || $year > 4 ||
    $semester === false || $semester < 1 || $semester > 6 ||
    !in_array($gender, $allowedGenders, true) ||
    ($field("terms") !== "accepted") ||
    !is_string($password) || strlen($password) < 8 ||
    !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)
) {
    json_response(["success" => false, "message" => "Please provide valid information in every required field."], 422);
}

if ($password !== $confirmPassword) {
    json_response(["success" => false, "message" => "Passwords do not match."], 422);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $mysqli->prepare(
        "INSERT INTO users (full_name, student_id, email, mobile, course, study_year, semester, gender, password)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssssiiss", $fullName, $studentId, $email, $mobile, $course, $year, $semester, $gender, $passwordHash);
    $stmt->execute();

    json_response(["success" => true, "message" => "Account created successfully."]);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        json_response(["success" => false, "message" => "An account with this email or Student ID already exists."], 409);
    }
    error_log("StudentHub registration failed: " . $e->getMessage());
    json_response(["success" => false, "message" => "Unable to create the account right now."], 500);
}
