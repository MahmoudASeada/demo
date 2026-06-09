<?php

require_once "db.php";

header("Content-Type: application/json");

$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";
$token = str_replace("Bearer ", "", $authHeader);

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "No token provided",
        "result" => []
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
        "message" => "Unauthorized",
        "result" => []
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT article_id
    FROM article_assignments
    WHERE user_id = ?
");

$stmt->execute([$user["id"]]);
$articleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$articleIds || count($articleIds) === 0) {
    echo json_encode([
        "success" => true,
        "result" => []
    ]);
    exit;
}

$ids = array_map(function($id){
    return '"' . addslashes($id) . '"';
}, $articleIds);

$idsString = implode(",", $ids);

$query = '*[
  _type=="article" &&
  active==true &&
  _id in [' . $idsString . ']
]|order(publishedDate desc){
  _id,
  title,
  smallTitle,
  description,
  category,
  readTime,
  publishedDate,
  featured,
  buttonText,
  buttonLink,
  "imageUrl": image.asset->url
}';

$url = "https://0b1rlnsm.api.sanity.io/v2021-10-21/data/query/production?query=" . urlencode($query);

$response = file_get_contents($url);

if ($response === false) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch articles",
        "result" => []
    ]);
    exit;
}

echo $response;