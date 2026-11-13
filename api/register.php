<?php
header("Content-Type: application/json");
require "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$name  = trim($data["full_name"] ?? "");
$email = strtolower(trim($data["email"] ?? ""));
$pass  = $data["password"] ?? "";

if ($name === "" || $email === "" || $pass === "") {
    echo json_encode(["success" => false, "message" => "Please complete all required fields."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Z0-9._%+-]+@gmail\.com$/i', $email)) {
    echo json_encode(["success" => false, "message" => "Please use a valid Gmail address ending in @gmail.com."]);
    exit;
}

$passwordErrors = [];
if (strlen($pass) < 8) $passwordErrors[] = "at least 8 characters";
if (!preg_match('/[A-Z]/', $pass)) $passwordErrors[] = "one uppercase letter";
if (!preg_match('/[a-z]/', $pass)) $passwordErrors[] = "one lowercase letter";
if (!preg_match('/[0-9]/', $pass)) $passwordErrors[] = "one number";
if (!preg_match('/[^A-Za-z0-9]/', $pass)) $passwordErrors[] = "one special character";

if ($passwordErrors) {
    echo json_encode(["success" => false, "message" => "Password must contain " . implode(", ", $passwordErrors) . "."]);
    exit;
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This email address is already registered."]);
    exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hash);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Unable to create your account. Please try again."]);
    exit;
}

echo json_encode(["success" => true, "message" => "Account created! You can now log in."]);
