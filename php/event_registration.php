<?php

require_once __DIR__ . "/auth.php";
require_login(true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["success" => false, "message" => "Method not allowed."], 405);
}
require_csrf();

$input = json_decode(file_get_contents("php://input"), true);
$eventId = is_array($input) ? filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT) : false;
if ($eventId === false || $eventId < 1) {
    json_response(["success" => false, "message" => "Select a valid event."], 422);
}

try {
    $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id) VALUES (:user_id, :event_id)");
    $stmt->execute([":user_id" => $_SESSION["user_id"], ":event_id" => $eventId]);
    json_response(["success" => true, "message" => "You are registered for this event."]);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        json_response(["success" => false, "message" => "You are already registered for this event, or it is no longer available."], 409);
    }
    error_log("StudentHub event registration failed: " . $e->getMessage());
    json_response(["success" => false, "message" => "Event registration failed."], 500);
}
