<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
        "path" => "/",
    ]);
    session_start();
}

$respond = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $respond(["success" => false, "message" => "Invalid request method."], 405);
}

$field = static fn(string $key): string => isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : "";
$clean = static function (string $value): string {
    return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', "", $value) ?? "");
};
$name = $clean($field("name"));
$email = strtolower($clean($field("email")));
$subject = $clean($field("subject"));
$message = $clean($field("message"));

if (
    !preg_match("/^[\p{L}][\p{L} .'-]{1,119}$/u", $name) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 ||
    $subject === "" || strlen($subject) > 160 ||
    $message === "" || strlen($message) > 5000
) {
    $respond(["success" => false, "message" => "Enter a valid name, email, subject, and message (up to 5,000 characters)."], 422);
}

$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "contact_submissions.json";
$file = @fopen($path, "c+");
if ($file === false) {
    error_log("StudentHub could not open contact storage.");
    $respond(["success" => false, "message" => "Your enquiry could not be saved right now."], 500);
}

try {
    if (!flock($file, LOCK_EX)) {
        throw new RuntimeException("Unable to lock contact storage.");
    }
    $contents = stream_get_contents($file);
    $records = $contents === false || trim($contents) === "" ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($records) || !array_is_list($records)) {
        throw new RuntimeException("Contact storage does not contain a JSON array.");
    }

    $records[] = [
        "name" => $name,
        "email" => $email,
        "subject" => $subject,
        "message" => $message,
        "submitted_at" => gmdate(DATE_ATOM),
        "user_id" => $_SESSION["user_id"] ?? null,
    ];
    $encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    rewind($file);
    if (!ftruncate($file, 0) || fwrite($file, $encoded . PHP_EOL) === false || !fflush($file)) {
        throw new RuntimeException("Unable to write contact storage.");
    }
    flock($file, LOCK_UN);
    fclose($file);
    $respond(["success" => true, "message" => "Your enquiry was sent successfully."]);
} catch (Throwable $e) {
    flock($file, LOCK_UN);
    fclose($file);
    error_log("StudentHub contact submission failed: " . $e->getMessage());
    $respond(["success" => false, "message" => "Your enquiry could not be saved right now."], 500);
}
