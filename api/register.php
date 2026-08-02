<?php

require_once "db.php";

$input = json_decode(file_get_contents("php://input"), true);

$name = trim($input["name"] ?? "");
$email = strtolower(trim($input["email"] ?? ""));
$password = $input["password"] ?? "";

if (!$name || !$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Name, email and password are required"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Email already exists"
    ]);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (name, email, password, approved)
    VALUES (?, ?, ?, 0)
");

$stmt->execute([
    $name,
    $email,
    $passwordHash
]);

echo json_encode([
    "success" => true,
    "message" => "Account created. Please wait for admin approval."
]);