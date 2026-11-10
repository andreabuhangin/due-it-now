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
$a = $conn->prepare("UPDATE users SET google_refresh_token = NULL WHERE id = ?");
$a->bind_param("i", $uid);
$a->execute();
$b = $conn->prepare("UPDATE tasks SET gcal_event_id = NULL WHERE user_id = ?");
$b->bind_param("i", $uid);
$b->execute();
echo json_encode(["success" => true, "message" => "Google Calendar disconnected."]);