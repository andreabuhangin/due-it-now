<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: ../index.php"); exit; }
$c = require "../config/google.php";
$_SESSION["g_state"] = bin2hex(random_bytes(16));
$q = http_build_query([
    "client_id"     => $c["client_id"],
    "redirect_uri"  => $c["redirect_uri"],
    "response_type" => "code",
    "scope"         => "https://www.googleapis.com/auth/calendar.events",
    "access_type"   => "offline",
    "prompt"        => "consent",
    "state"         => $_SESSION["g_state"],
]);
header("Location: https://accounts.google.com/o/oauth2/v2/auth?" . $q);