<?php

require "db.php";

header("Content-Type: application/json");

$sql = "
SELECT
    sr.id,
    sr.survey_id,
    sr.user_id,
    sr.submitted_at,
    sr.survey_title,
    u.name,
    u.email
FROM survey_responses sr
INNER JOIN users u
ON u.id = sr.user_id
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