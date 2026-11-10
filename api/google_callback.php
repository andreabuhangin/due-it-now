<?php
session_start();
require "../config/db.php";
require "google_helper.php";

if (!isset($_SESSION["user_id"])) { header("Location: ../index.php"); exit; }
$uid = $_SESSION["user_id"];

if (empty($_GET["code"]) || !hash_equals($_SESSION["g_state"] ?? "", $_GET["state"] ?? "")) {
    header("Location: ../dashboard.php#settings");
    exit;
}

$c = g_config();
[$code, $res] = g_http("https://oauth2.googleapis.com/token", "POST", http_build_query([
    "code"          => $_GET["code"],
    "client_id"     => $c["client_id"],
    "client_secret" => $c["client_secret"],
    "redirect_uri"  => $c["redirect_uri"],
    "grant_type"    => "authorization_code",
]), null, false);

if (!empty($res["refresh_token"])) {
    $stmt = $conn->prepare("UPDATE users SET google_refresh_token = ? WHERE id = ?");
    $stmt->bind_param("si", $res["refresh_token"], $uid);
    $stmt->execute();

    // Send existing pending tasks to Google once
    $q = $conn->prepare("SELECT id, title, description, due_date, due_time FROM tasks
                         WHERE user_id = ? AND status = 'pending' AND due_date IS NOT NULL AND gcal_event_id IS NULL");
    $q->bind_param("i", $uid);
    $q->execute();
    foreach ($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        g_sync_task($conn, $uid, $row["id"], [
            "title" => $row["title"], "desc" => $row["description"],
            "due" => $row["due_date"], "time" => $row["due_time"],
        ], "pending");
    }
}
header("Location: ../dashboard.php#calendar");