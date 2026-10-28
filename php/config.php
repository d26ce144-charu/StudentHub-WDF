<?php

$host = getenv("STUDENTHUB_DB_HOST") ?: "localhost";
$dbname = getenv("STUDENTHUB_DB_NAME") ?: "studenthub";
$username = getenv("STUDENTHUB_DB_USER") ?: "root";
$password = getenv("STUDENTHUB_DB_PASSWORD") ?: "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = new mysqli($host, $username, $password, $dbname);
    $mysqli->set_charset("utf8mb4");
} catch (PDOException | mysqli_sql_exception $e) {
    error_log("StudentHub database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Check the StudentHub database configuration.");
}