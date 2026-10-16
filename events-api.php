<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require "db.php";

function respond($success, $message, $data = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit();
}

if (!isset($_SESSION["student_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin") {
    respond(false, "Admin login required.", null, 403);
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $result = $conn->query(
        "SELECT event_id, event_name, event_date, location, description
         FROM events ORDER BY event_id DESC"
    );

    if (!$result) {
        respond(false, "Failed to retrieve events.", null, 500);
    }

    $events = [];

    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    respond(true, "Events retrieved successfully.", $events);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond(false, "Invalid JSON data.", null, 400);
}

if ($method === "POST" || $method === "PUT") {
    $name = trim($input["event_name"] ?? "");
    $date = trim($input["event_date"] ?? "");
    $location = trim($input["location"] ?? "");
    $description = trim($input["description"] ?? "");

    if ($name === "" || $date === "" || $location === "") {
        respond(false, "Name, date, and location are required.", null, 400);
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) ||
        !checkdate(
            (int)substr($date, 5, 2),
            (int)substr($date, 8, 2),
            (int)substr($date, 0, 4)
        )) {
        respond(false, "Enter a valid event date.", null, 400);
    }

    if ($method === "POST") {
        $stmt = $conn->prepare(
            "INSERT INTO events
             (event_name, event_date, location, description)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param("ssss", $name, $date, $location, $description);

        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            respond(true, "Event added successfully.", ["event_id" => $id], 201);
        }

        $stmt->close();
        respond(false, "Failed to add event.", null, 500);
    }

    $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        respond(false, "A valid event ID is required.", null, 400);
    }

    $stmt = $conn->prepare(
        "UPDATE events
         SET event_name = ?, event_date = ?, location = ?, description = ?
         WHERE event_id = ?"
    );

    $stmt->bind_param("ssssi", $name, $date, $location, $description, $id);

    if ($stmt->execute()) {
        $changed = $stmt->affected_rows;
        $stmt->close();

        if ($changed === 0) {
            $check = $conn->prepare(
                "SELECT event_id FROM events WHERE event_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows > 0;
            $check->close();

            if (!$exists) {
                respond(false, "Event not found.", null, 404);
            }
        }

        respond(true, "Event updated successfully.");
    }

    $stmt->close();
    respond(false, "Failed to update event.", null, 500);
}

if ($method === "DELETE") {
    $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        respond(false, "A valid event ID is required.", null, 400);
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            respond(false, "Event not found.", null, 404);
        }

        respond(true, "Event deleted successfully.");
    }

    $stmt->close();
    respond(false, "Failed to delete event.", null, 500);
}

header("Allow: GET, POST, PUT, DELETE");
respond(false, "Method not allowed.", null, 405);
?>
