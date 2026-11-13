<?php
header("Content-Type: application/json");
require "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$name  = trim($data["full_name"] ?? "");
$email = strtolower(trim($data["email"] ?? ""));
$pass  = $data["password"] ?? "";

if ($name === "" || $email === "" || $pass === "") {
    echo json_encode(["success" => false, "message" => "Please complete all required fields."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit;
}
if (!preg_match('/^[a-z0-9._%+\-]+@gmail\.com$/', $email)) {
    echo json_encode(["success" => false, "message" => "Please use a Gmail address (name@gmail.com)."]);
    exit;
}
if (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[a-z]/', $pass) || !preg_match('/\d/', $pass)) {
    echo json_encode(["success" => false, "message" => "Password needs 8+ characters with an uppercase letter, a lowercase letter and a number."]);
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