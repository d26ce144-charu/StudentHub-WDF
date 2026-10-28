<?php

require_once __DIR__ . "/auth.php";
require_admin(true);
$resource = "students";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $search = isset($_GET["search"]) && is_string($_GET["search"]) ? trim($_GET["search"]) : "";
    $course = isset($_GET["course"]) && is_string($_GET["course"]) ? trim($_GET["course"]) : "";
    $sql = "SELECT id, student_id, full_name, email, course, study_year, semester FROM students
            WHERE (? = '' OR full_name LIKE CONCAT('%', ?, '%') OR student_id LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%'))
            AND (? = '' OR course = ?)
            ORDER BY created_at DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssss", $search, $search, $search, $search, $course, $course);
    $stmt->execute();
    $result = $stmt->get_result();
    json_response(["success" => true, "students" => $result->fetch_all(MYSQLI_ASSOC)]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["success" => false, "message" => "Method not allowed."], 405);
}
require_csrf();

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    json_response(["success" => false, "message" => "Invalid JSON request."], 400);
}
$field = static fn(string $key): string => isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : "";
$action = $field("action");
$id = isset($input["id"]) && (is_string($input["id"]) || is_int($input["id"])) ? filter_var($input["id"], FILTER_VALIDATE_INT) : false;

try {
    $mysqli->begin_transaction();
    if ($action === "delete") {
        if (!$id || $id < 1) {
            json_response(["success" => false, "message" => "Select a valid student."], 422);
        }
        $stmt = $mysqli->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            json_response(["success" => false, "message" => "Student not found."], 404);
        }
    } elseif ($action === "create" || $action === "update") {
        $studentId = $field("student_id");
        $fullName = $field("full_name");
        $email = strtolower($field("email"));
        $course = $field("course");
        $year = filter_var($field("study_year"), FILTER_VALIDATE_INT);
        $semester = filter_var($field("semester"), FILTER_VALIDATE_INT);
        if (
            !preg_match("/^[A-Za-z0-9-]{2,40}$/", $studentId) ||
            !preg_match("/^[\p{L}][\p{L} .'-]{1,119}$/u", $fullName) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 ||
            $course === "" || strlen($course) > 120 ||
            $year === false || $year < 1 || $year > 4 ||
            $semester === false || $semester < 1 || $semester > 8 ||
            ($action === "update" && (!$id || $id < 1))
        ) {
            json_response(["success" => false, "message" => "Enter valid student details."], 422);
        }

        if ($action === "create") {
            $stmt = $mysqli->prepare(
                "INSERT INTO students (student_id, full_name, email, course, study_year, semester) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssii", $studentId, $fullName, $email, $course, $year, $semester);
            $stmt->execute();
            $entityId = $stmt->insert_id;
        } else {
            $stmt = $mysqli->prepare(
                "UPDATE students SET student_id = ?, full_name = ?, email = ?, course = ?, study_year = ?, semester = ? WHERE id = ?"
            );
            $stmt->bind_param("ssssiii", $studentId, $fullName, $email, $course, $year, $semester, $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $check = $mysqli->prepare("SELECT id FROM students WHERE id = ?");
                $check->bind_param("i", $id);
                $check->execute();
                if (!$check->get_result()->fetch_assoc()) {
                    json_response(["success" => false, "message" => "Student not found."], 404);
                }
            }
            $entityId = $id;
        }
        $auditAction = $action === "create" ? "student.created" : "student.updated";
        $stmt = $mysqli->prepare("INSERT INTO audit_logs (actor_id, action, entity, entity_id) VALUES (?, ?, 'student', ?)");
        $actorId = (int)$_SESSION["user_id"];
        $stmt->bind_param("isi", $actorId, $auditAction, $entityId);
        $stmt->execute();
    } else {
        json_response(["success" => false, "message" => "Unknown student operation."], 400);
    }

    if ($action === "delete") {
        $auditAction = "student.deleted";
        $stmt = $mysqli->prepare("INSERT INTO audit_logs (actor_id, action, entity, entity_id) VALUES (?, ?, ?, ?)");
        $actorId = (int)$_SESSION["user_id"];
        $stmt->bind_param("issi", $actorId, $auditAction, $resource, $id);
        $stmt->execute();
    }
    $mysqli->commit();
    json_response(["success" => true, "message" => "Student " . ($action === "delete" ? "deleted" : ($action === "create" ? "added" : "updated")) . " successfully."]);
} catch (mysqli_sql_exception $e) {
    $mysqli->rollback();
    if ($e->getCode() === 1062) {
        json_response(["success" => false, "message" => "Student ID or email is already in use."], 409);
    }
    error_log("StudentHub student operation failed: " . $e->getMessage());
    json_response(["success" => false, "message" => "Student operation failed."], 500);
}
