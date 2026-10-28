<?php

require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $stmt = $pdo->query(
        "SELECT id, title, description, DATE_FORMAT(event_date, '%Y-%m-%d') AS date,
                event_time AS time, venue, category, poster_path
         FROM events ORDER BY event_date, event_time"
    );
    json_response(["success" => true, "events" => $stmt->fetchAll()]);
}

require_admin(true);
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["success" => false, "message" => "Method not allowed."], 405);
}
require_csrf();

$field = static fn(string $key): string => isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : "";
$action = $field("action");
$id = filter_var($field("id"), FILTER_VALIDATE_INT);
$posterPath = null;
$posterUpload = null;
$savedPosterPath = null;
$uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "event-posters";

try {
    if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($action !== "create" && $action !== "update") {
            json_response(["success" => false, "message" => "A poster can only be uploaded when creating or updating an event."], 422);
        }
        $upload = $_FILES["poster"];
        if ($upload["error"] !== UPLOAD_ERR_OK || $upload["size"] > 2 * 1024 * 1024) {
            json_response(["success" => false, "message" => "Poster upload failed or exceeds the 2 MB limit."], 422);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload["tmp_name"]);
        $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        if (!isset($extensions[$mime]) || @getimagesize($upload["tmp_name"]) === false) {
            json_response(["success" => false, "message" => "Poster must be a valid JPEG, PNG, or WebP image."], 422);
        }
        $posterUpload = ["temporary_path" => $upload["tmp_name"], "extension" => $extensions[$mime]];
    }

    if ($action === "delete") {
        if (!$id || $id < 1) {
            json_response(["success" => false, "message" => "Select a valid event."], 422);
        }
        $find = $pdo->prepare("SELECT poster_path FROM events WHERE id = :id");
        $find->execute([":id" => $id]);
        $event = $find->fetch();
        if (!$event) {
            json_response(["success" => false, "message" => "Event not found."], 404);
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $oldPoster = $event["poster_path"];
        $actionLabel = "event.deleted";
        $entityId = $id;
    } elseif ($action === "create" || $action === "update") {
        $title = $field("title");
        $description = $field("description");
        $date = $field("date");
        $time = $field("time");
        $venue = $field("venue");
        $category = $field("category");
        $validDate = DateTime::createFromFormat("!Y-m-d", $date);
        if (
            $title === "" || strlen($title) > 200 ||
            $description === "" || strlen($description) > 5000 ||
            !$validDate || $validDate->format("Y-m-d") !== $date ||
            $time === "" || strlen($time) > 30 ||
            $venue === "" || strlen($venue) > 160 ||
            $category === "" || strlen($category) > 80 ||
            ($action === "update" && (!$id || $id < 1))
        ) {
            json_response(["success" => false, "message" => "Enter valid event information."], 422);
        }

        $existing = null;
        if ($action === "update") {
            $find = $pdo->prepare("SELECT poster_path FROM events WHERE id = :id");
            $find->execute([":id" => $id]);
            $existing = $find->fetch();
            if (!$existing) {
                json_response(["success" => false, "message" => "Event not found."], 404);
            }
        }
        $pdo->beginTransaction();
        if ($posterUpload !== null) {
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException("Unable to create poster directory.");
            }
            $filename = bin2hex(random_bytes(16)) . "." . $posterUpload["extension"];
            if (!move_uploaded_file($posterUpload["temporary_path"], $uploadDirectory . DIRECTORY_SEPARATOR . $filename)) {
                throw new RuntimeException("Unable to save poster upload.");
            }
            $savedPosterPath = "uploads/event-posters/" . $filename;
            $posterPath = $savedPosterPath;
        }

        if ($action === "create") {
            $stmt = $pdo->prepare(
                "INSERT INTO events (title, description, event_date, event_time, venue, category, poster_path)
                 VALUES (:title, :description, :event_date, :event_time, :venue, :category, :poster_path)"
            );
            $stmt->execute([
                ":title" => $title, ":description" => $description, ":event_date" => $date,
                ":event_time" => $time, ":venue" => $venue, ":category" => $category, ":poster_path" => $posterPath,
            ]);
            $entityId = (int)$pdo->lastInsertId();
            $actionLabel = "event.created";
        } else {
            $posterPath = $posterPath ?? $existing["poster_path"];
            $stmt = $pdo->prepare(
                "UPDATE events SET title = :title, description = :description, event_date = :event_date,
                 event_time = :event_time, venue = :venue, category = :category, poster_path = :poster_path WHERE id = :id"
            );
            $stmt->execute([
                ":title" => $title, ":description" => $description, ":event_date" => $date,
                ":event_time" => $time, ":venue" => $venue, ":category" => $category,
                ":poster_path" => $posterPath, ":id" => $id,
            ]);
            $oldPoster = $existing["poster_path"] !== $posterPath ? $existing["poster_path"] : null;
            $entityId = $id;
            $actionLabel = "event.updated";
        }
    } else {
        json_response(["success" => false, "message" => "Unknown event operation."], 400);
    }

    $audit = $pdo->prepare("INSERT INTO audit_logs (actor_id, action, entity, entity_id) VALUES (:actor, :action, 'event', :entity_id)");
    $audit->execute([":actor" => $_SESSION["user_id"], ":action" => $actionLabel, ":entity_id" => $entityId]);
    $pdo->commit();
    if (!empty($oldPoster) && preg_match('#^uploads/event-posters/[a-f0-9]{32}\.(jpg|png|webp)$#', $oldPoster)) {
        $oldPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $oldPoster);
        if (is_file($oldPath)) {
            unlink($oldPath);
        }
    }
    json_response(["success" => true, "message" => "Event " . ($action === "delete" ? "deleted" : ($action === "create" ? "created" : "updated")) . " successfully."]);
} catch (PDOException | RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($savedPosterPath !== null) {
        $savedPoster = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $savedPosterPath);
        if (is_file($savedPoster)) {
            unlink($savedPoster);
        }
    }
    error_log("StudentHub event operation failed: " . $e->getMessage());
    json_response(["success" => false, "message" => "Event operation failed."], 500);
}
