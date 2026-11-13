<?php
header("Content-Type: application/json");
require "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$name  = trim($data["full_name"] ?? "");
$email = trim($data["email"] ?? "");
$pass  = $data["password"] ?? "";

if ($name === "" || $email === "" || $pass === "") {
    echo json_encode(["success" => false, "message" => "Please complete all required fields."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit;
}
if (strlen($pass) < 8) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters."]);
    exit;
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email is already registered."]);
    exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hash);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Account created! You can now log in."]);