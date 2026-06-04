<?php

require "db.php";

header("Content-Type: application/json");

$headers = getallheaders();

if(
  !isset($headers["Authorization"]) ||
  $headers["Authorization"] !== "Bearer admin123"
){
  echo json_encode([
    "success" => false,
    "message" => "Unauthorized"
  ]);
  exit;
}

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
ORDER BY sr.id DESC
";

$result = $conn->query($sql);

$responses = [];

while($row = $result->fetch_assoc()){
    $responses[] = $row;
}

echo json_encode([
    "success" => true,
    "responses" => $responses
]);