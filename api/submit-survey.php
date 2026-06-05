<?php

require_once "db.php";

header("Content-Type: application/json");

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

$input = json_decode(file_get_contents("php://input"), true);

$surveyId = (int)($input["surveyId"] ?? 0);
$answers = $input["answers"] ?? [];

if (!$surveyId || !count($answers)) {
    echo json_encode([
        "success" => false,
        "message" => "Survey ID and answers are required"
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

$stmt = $pdo->prepare("
    SELECT id, title, status
    FROM surveys
    WHERE id = ? AND assigned_user_id = ?
    LIMIT 1
");

$stmt->execute([$surveyId, $user["id"]]);
$survey = $stmt->fetch();

if (!$survey) {
    echo json_encode([
        "success" => false,
        "message" => "Survey not found"
    ]);
    exit;
}

if ($survey["status"] === "completed") {
    echo json_encode([
        "success" => false,
        "message" => "This survey has already been submitted"
    ]);
    exit;
}

$pdo->beginTransaction();

try {

    $responseStmt = $pdo->prepare("
        INSERT INTO survey_responses 
        (user_id, survey_id, survey_title, status)
        VALUES (?, ?, ?, 'completed')
    ");

    $responseStmt->execute([
        $user["id"],
        $survey["id"],
        $survey["title"]
    ]);

    $responseId = $pdo->lastInsertId();

    $answerStmt = $pdo->prepare("
        INSERT INTO survey_answers 
        (response_id, question_id, question_label, answer)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($answers as $answer) {
        $questionId = (int)($answer["questionId"] ?? 0);
        $questionLabel = trim($answer["questionLabel"] ?? "");
        $answerText = trim($answer["answer"] ?? "");

        if (!$questionId || !$questionLabel || !$answerText) {
            continue;
        }

        $answerStmt->execute([
            $responseId,
            $questionId,
            $questionLabel,
            $answerText
        ]);
    }

    $updateStmt = $pdo->prepare("
        UPDATE surveys
        SET status = 'completed'
        WHERE id = ?
    ");

    $updateStmt->execute([$survey["id"]]);

    $pdo->commit();

    sendSurveyEmail($user, $survey, $answers);

    echo json_encode([
        "success" => true,
        "message" => "Survey submitted successfully"
    ]);
    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Failed to submit survey"
    ]);
    exit;
}

function sendSurveyEmail($user, $survey, $answers) {

    $to = "seowzone@gmail.com";
    $subject = "New Survey Response - " . $survey["title"];

    $csv = "User Name,User Email,Survey Title\n";
    $csv .= csvValue($user["name"]) . "," . csvValue($user["email"]) . "," . csvValue($survey["title"]) . "\n\n";
    $csv .= "Question,Answer\n";

    foreach ($answers as $answer) {
        $question = $answer["questionLabel"] ?? "";
        $answerText = $answer["answer"] ?? "";

        $csv .= csvValue($question) . "," . csvValue($answerText) . "\n";
    }

    $fileName = "survey-response-" . date("Y-m-d-H-i-s") . ".csv";
    $boundary = md5(time());

    $headers = "From: W Zone Portal <no-reply@wzone.local>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

    $message = "--" . $boundary . "\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= "New survey response submitted.\n\n";
    $message .= "User: " . $user["name"] . "\n";
    $message .= "Email: " . $user["email"] . "\n";
    $message .= "Survey: " . $survey["title"] . "\n\n";

    $message .= "--" . $boundary . "\r\n";
    $message .= "Content-Type: text/csv; name=\"" . $fileName . "\"\r\n";
    $message .= "Content-Disposition: attachment; filename=\"" . $fileName . "\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($csv)) . "\r\n";
    $message .= "--" . $boundary . "--";

    @mail($to, $subject, $message, $headers);
}

function csvValue($value) {
    $value = (string)$value;
    $value = str_replace('"', '""', $value);
    return '"' . $value . '"';
}