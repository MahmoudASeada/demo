<?php

require_once "db.php";

$input = json_decode(file_get_contents("php://input"), true);

$email = strtolower(trim($input["email"] ?? ""));
$password = $input["password"] ?? "";

$ADMIN_EMAIL = "admin@wzone.com";
$ADMIN_PASSWORD = "Admin@12345";

if (!$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
    exit;
}

if ($email !== $ADMIN_EMAIL || $password !== $ADMIN_PASSWORD) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid admin credentials"
    ]);
    exit;
}

$token = bin2hex(random_bytes(32));

echo json_encode([
    "success" => true,
    "message" => "Admin login successful",
    "token" => $token
]);