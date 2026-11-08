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

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $stmt = $conn->prepare("SELECT full_name, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    echo json_encode(["success" => true, "user" => $stmt->get_result()->fetch_assoc()]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data["action"] ?? "";

if ($action === "name") {
    $name = trim($data["full_name"] ?? "");
    if ($name === "" || mb_strlen($name) > 100) {
        echo json_encode(["success" => false, "message" => "Enter a name (up to 100 characters)."]);
        exit;
    }
    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $uid);
    $stmt->execute();
    $_SESSION["full_name"] = $name;
    echo json_encode(["success" => true, "message" => "Profile updated."]);
    exit;
}

if ($action === "password") {
    $current = $data["current"] ?? "";
    $new = $data["new"] ?? "";
    if (strlen($new) < 8) {
        echo json_encode(["success" => false, "message" => "New password must be at least 8 characters."]);
        exit;
    }
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !password_verify($current, $row["password_hash"])) {
        echo json_encode(["success" => false, "message" => "Current password is incorrect."]);
        exit;
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $uid);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Password changed."]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request."]);