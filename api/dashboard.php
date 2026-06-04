<?php

require_once "db.php";

$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";

$token = str_replace("Bearer ", "", $authHeader);

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "No token provided"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name, email, approved
    FROM users
    WHERE session_token = ?
    LIMIT 1
");

$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || (int)$user["approved"] !== 1) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$userId = $user["id"];

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM survey_responses
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$totalResponses = $stmt->fetch()["total"];

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM survey_responses
    WHERE user_id = ? AND status = 'completed'
");

$stmt->execute([$userId]);
$completedSurveys = $stmt->fetch()["total"];

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"]
    ],
    "stats" => [
        "submittedSurveys" => (int)$completedSurveys,
        "pendingResponses" => 0,
        "responsesReceived" => (int)$totalResponses,
        "articles" => 0
    ],
    "surveys" => [],
    "articles" => []
]);