<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'dueitnow';
$port = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_init();

$flags = 0;
if (getenv('DB_HOST')) { // online only: use SSL for the cloud database
    $conn->ssl_set(null, null, '/etc/ssl/certs/ca-certificates.crt', null, null);
    $flags = MYSQLI_CLIENT_SSL;
}

if (!@$conn->real_connect($host, $user, $pass, $name, $port, null, $flags)) {
    http_response_code(500);
    die(json_encode(["error" => "Unable to connect to the server."]));
}
$conn->set_charset("utf8mb4");