<?php

require_once "db.php";

header("Content-Type: application/json");

$headers = getallheaders();

if(
  !isset($headers["Authorization"]) ||
  !$headers["Authorization"]
){
  echo json_encode([
    "success" => false,
    "message" => "Unauthorized"
  ]);
  exit;
}

$surveyId = (int)($_GET["survey_id"] ?? 0);

$sql = "
SELECT
    sr.id,
    sr.survey_id,
    sr.user_id,
    sr.submitted_at,
    u.name,
    u.email,
    s.title
FROM survey_responses sr
INNER JOIN users u ON u.id = sr.user_id
INNER JOIN surveys s ON s.id = sr.survey_id
";

if ($surveyId) {
    $sql .= " WHERE sr.survey_id = " . $surveyId;
}

$sql .= " ORDER BY sr.id DESC";

$result = $pdo->query($sql);

$responses = [];

while($row = $result->fetch()){
    $responses[] = $row;
}

echo json_encode([
    "success" => true,
    "responses" => $responses
]);