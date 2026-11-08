<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please log in first."]);
    exit;
}
$uid = $_SESSION["user_id"];
$method = $_SERVER["REQUEST_METHOD"];

// READ: list this user's tasks
if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, title, description, category, priority, due_date, status FROM tasks WHERE user_id = ? ORDER BY due_date ASC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(["success" => true, "tasks" => $rows]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// CREATE
if ($method === "POST") {
    $title = trim($data["title"] ?? "");
    if ($title === "") {
        echo json_encode(["success" => false, "message" => "Title is required."]);
        exit;
    }
    $desc = trim($data["description"] ?? "");
    $cat  = trim($data["category"] ?? "");
    $pri  = in_array($data["priority"] ?? "", ["low","medium","high"]) ? $data["priority"] : "medium";
    $due  = ($data["due_date"] ?? "") !== "" ? $data["due_date"] : null;
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, category, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $uid, $title, $desc, $cat, $pri, $due);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Task added."]);
    exit;
}

// UPDATE
if ($method === "PUT") {
    $id    = (int)($data["id"] ?? 0);
    $title = trim($data["title"] ?? "");
    if ($title === "") {
        echo json_encode(["success" => false, "message" => "Title is required."]);
        exit;
    }
    $desc = trim($data["description"] ?? "");
    $cat  = trim($data["category"] ?? "");
    $pri  = in_array($data["priority"] ?? "", ["low","medium","high"]) ? $data["priority"] : "medium";
    $due  = ($data["due_date"] ?? "") !== "" ? $data["due_date"] : null;
    $st   = ($data["status"] ?? "") === "completed" ? "completed" : "pending";
    $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, category=?, priority=?, due_date=?, status=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssssssii", $title, $desc, $cat, $pri, $due, $st, $id, $uid);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Task updated."]);
    exit;
}

// DELETE
if ($method === "DELETE") {
    $id = (int)($data["id"] ?? 0);
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Task deleted."]);
    exit;
}