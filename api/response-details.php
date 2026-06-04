<?php

require "db.php";

header("Content-Type: application/json");

$id = intval($_GET["id"]);

$sql = "
SELECT
  sa.answer,
  sq.question_text
FROM survey_answers sa
INNER JOIN survey_questions sq
ON sa.question_id = sq.id
WHERE sa.response_id = $id
";

$result = $conn->query($sql);

$answers = [];

while($row = $result->fetch_assoc()){
  $answers[] = $row;
}

echo json_encode([
  "success"=>true,
  "answers"=>$answers
]);