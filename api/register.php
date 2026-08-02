<?php

// 1. السماح بطلبات الـ POST و الـ OPTIONS من أي مكان
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// 2. إنهاء طلب الـ Preflight (OPTIONS) فوراً بنجاح
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. التأكد من إن الطلب POST فقط
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method Not Allowed. Use POST."
    ]);
    exit();
}

require_once "db.php";

$input = json_decode(file_get_contents("php://input"), true);

$name = trim($input["name"] ?? "");
$email = strtolower(trim($input["email"] ?? ""));
$password = $input["password"] ?? "";

if (!$name || !$email || !$password) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Name, email and password are required"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    http_response_code(409);
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

http_response_code(201);
echo json_encode([
    "success" => true,
    "message" => "Account created. Please wait for admin approval."
]);