<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";
   require "google_helper.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please log in first."]);
    exit;
}
$uid = $_SESSION["user_id"];
$method = $_SERVER["REQUEST_METHOD"];

// READ
if ($method === "GET") {
   $stmt = $conn->prepare("SELECT id, title, description, category, priority, due_date, due_time, status, completed_at FROM tasks WHERE user_id = ? ORDER BY due_date ASC, due_time ASC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    echo json_encode(["success" => true, "tasks" => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Shared cleaning for create and update
$clean = function ($data) {
    $time = trim($data["due_time"] ?? "");
    return [
        "title" => trim($data["title"] ?? ""),
        "desc"  => trim($data["description"] ?? ""),
        "cat"   => trim($data["category"] ?? ""),
        "pri"   => in_array($data["priority"] ?? "", ["low", "medium", "high"]) ? $data["priority"] : "medium",
        "due"   => ($data["due_date"] ?? "") !== "" ? $data["due_date"] : null,
        "time"  => preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) ? $time : null,
    ];
};

// CREATE
if ($method === "POST") {
    $t = $clean($data);
    if ($t["title"] === "") {
        echo json_encode(["success" => false, "message" => "Title is required."]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, category, priority, due_date, due_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $uid, $t["title"], $t["desc"], $t["cat"], $t["pri"], $t["due"], $t["time"]);
    $stmt->execute();
       g_sync_task($conn, $uid, $conn->insert_id, $t, "pending");
    echo json_encode(["success" => true, "message" => "Task added."]);
    exit;
}

// UPDATE
if ($method === "PUT") {
    $t = $clean($data);
    $id = (int)($data["id"] ?? 0);
    if ($t["title"] === "") {
        echo json_encode(["success" => false, "message" => "Title is required."]);
        exit;
    }
    $st = ($data["status"] ?? "") === "completed" ? "completed" : "pending";
    $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, category=?, priority=?, due_date=?, due_time=?, status=?, completed_at = IF(?='completed', COALESCE(completed_at, NOW()), NULL) WHERE id=? AND user_id=?");
$stmt->bind_param("ssssssssii", $t["title"], $t["desc"], $t["cat"], $t["pri"], $t["due"], $t["time"], $st, $st, $id, $uid);
    $stmt->execute();
       g_sync_task($conn, $uid, $id, $t, $st);
    echo json_encode(["success" => true, "message" => "Task updated."]);
    exit;
}

// DELETE
if ($method === "DELETE") {
    $id = (int)($data["id"] ?? 0);
       g_sync_delete($conn, $uid, $id);
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Task deleted."]);
    exit;
}