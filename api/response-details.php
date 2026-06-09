<?php

require_once "db.php";

header("Content-Type: application/json");

$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        answer,
        question_label AS question_text
    FROM survey_answers
    WHERE response_id = ?
    ORDER BY id ASC
");

$stmt->execute([$id]);

echo json_encode([
    "success" => true,
    "answers" => $stmt->fetchAll()
]);