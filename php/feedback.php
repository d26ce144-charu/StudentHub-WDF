<?php

require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["success" => false, "message" => "Invalid request method."], 405);
}

$field = static fn(string $key): string => isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : "";
$name = $field("name");
$rating = filter_var($field("rating"), FILTER_VALIDATE_INT);
$message = $field("feedback");
if (
    !preg_match("/^[\p{L}][\p{L} .'-]{1,119}$/u", $name) ||
    $rating === false || $rating < 1 || $rating > 5 ||
    $message === "" || strlen($message) > 5000
) {
    json_response(["success" => false, "message" => "Enter a valid name, rating, and feedback message."], 422);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO feedback (user_id, name, rating, message)
         VALUES (:user_id, :name, :rating, :message)"
    );
    $stmt->execute([
        ":user_id" => $_SESSION["user_id"] ?? null,
        ":name" => $name,
        ":rating" => $rating,
        ":message" => $message,
    ]);
    json_response(["success" => true, "message" => "Thank you for your feedback."]);
} catch (PDOException $e) {
    error_log("StudentHub feedback submission failed: " . $e->getMessage());
    json_response(["success" => false, "message" => "Your feedback could not be saved right now."], 500);
}
