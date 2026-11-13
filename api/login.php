<?php
session_start();
header("Content-Type: application/json");
require "../config/db.php";

$data  = json_decode(file_get_contents("php://input"), true) ?? [];
$email = strtolower(trim($data["email"] ?? ""));
$pass  = $data["password"] ?? "";

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($pass, $user["password_hash"])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

session_regenerate_id(true);
$_SESSION["user_id"]   = $user["id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["role"]      = $user["role"];

echo json_encode(["success" => true, "message" => "Login successful."]);
