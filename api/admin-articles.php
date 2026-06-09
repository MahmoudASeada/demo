<?php

require_once "db.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

function getSanityArticles() {
    $query = '*[_type=="article" && active==true]|order(publishedDate desc){
      _id,
      title,
      category,
      featured,
      publishedDate
    }';

    $url = "https://0b1rlnsm.api.sanity.io/v2021-10-21/data/query/production?query=" . urlencode($query);

    $response = file_get_contents($url);

    if ($response === false) {
        return [];
    }

    $json = json_decode($response, true);

    return $json["result"] ?? [];
}

if ($method === "GET") {

    $usersStmt = $pdo->query("
        SELECT id, name, email
        FROM users
        WHERE approved = 1
        ORDER BY name ASC
    ");

    $assignStmt = $pdo->query("
        SELECT article_id, user_id
        FROM article_assignments
    ");

    $assignments = [];

    foreach ($assignStmt->fetchAll() as $row) {
        $articleId = $row["article_id"];

        if (!isset($assignments[$articleId])) {
            $assignments[$articleId] = [];
        }

        $assignments[$articleId][] = (int)$row["user_id"];
    }

    echo json_encode([
        "success" => true,
        "articles" => getSanityArticles(),
        "users" => $usersStmt->fetchAll(),
        "assignments" => $assignments
    ]);
    exit;
}

if ($method === "POST") {

    $input = json_decode(file_get_contents("php://input"), true);

    $articleId = trim($input["articleId"] ?? "");
    $userIds = $input["userIds"] ?? [];

    if (!$articleId) {
        echo json_encode([
            "success" => false,
            "message" => "Article ID is required"
        ]);
        exit;
    }

    if (!is_array($userIds)) {
        $userIds = [];
    }

    $pdo->beginTransaction();

    try {

        $deleteStmt = $pdo->prepare("
            DELETE FROM article_assignments
            WHERE article_id = ?
        ");
        $deleteStmt->execute([$articleId]);

        $insertStmt = $pdo->prepare("
            INSERT IGNORE INTO article_assignments (article_id, user_id)
            VALUES (?, ?)
        ");

        foreach ($userIds as $userId) {
            $userId = (int)$userId;

            if ($userId > 0) {
                $insertStmt->execute([
                    $articleId,
                    $userId
                ]);
            }
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Article users updated successfully"
        ]);
        exit;

    } catch (Exception $e) {

        $pdo->rollBack();

        echo json_encode([
            "success" => false,
            "message" => "Failed to update article users"
        ]);
        exit;
    }
}

echo json_encode([
    "success" => false,
    "message" => "Invalid request"
]);