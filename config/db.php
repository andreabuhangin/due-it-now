<?php
$conn = new mysqli("localhost", "root", "", "dueitnow");
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Unable to connect to the server."]));
}
$conn->set_charset("utf8mb4");