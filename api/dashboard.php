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
    SELECT id, name, email, approved, profile_image
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
    FROM surveys
    WHERE assigned_user_id = ?
");
$stmt->execute([$userId]);
$totalSurveys = (int)$stmt->fetch()["total"];

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM surveys
    WHERE assigned_user_id = ? AND status = 'pending'
");
$stmt->execute([$userId]);
$pendingSurveys = (int)$stmt->fetch()["total"];

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM surveys
    WHERE assigned_user_id = ? AND status = 'completed'
");
$stmt->execute([$userId]);
$completedSurveys = (int)$stmt->fetch()["total"];

$stmt = $pdo->prepare("
    SELECT id, title, status, created_at
    FROM surveys
    WHERE assigned_user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$surveys = $stmt->fetchAll();

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "profile_image" => $user["profile_image"]
    ],
    "stats" => [
        "submittedSurveys" => $completedSurveys,
        "pendingResponses" => $pendingSurveys,
        "responsesReceived" => $totalSurveys,
        "articles" => 0
    ],
    "surveys" => $surveys,
    "articles" => []
]);