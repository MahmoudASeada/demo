<?php

require_once "db.php";

header("Content-Type: application/json");

$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("
    SELECT
        sa.question_id,
        sa.answer,
        sa.question_label AS question_text,
        suf.id AS file_id,
        suf.original_name AS file_name,
        suf.file_size
    FROM survey_answers sa
    LEFT JOIN survey_uploaded_files suf
        ON suf.response_id = sa.response_id
        AND suf.question_id = sa.question_id
    WHERE sa.response_id = ?
    ORDER BY sa.id ASC
");

$stmt->execute([$id]);

echo json_encode([
    "success" => true,
    "answers" => $stmt->fetchAll()
]);