<?php

require_once "db.php";

$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";
$token = str_replace("Bearer ", "", $authHeader);

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {

    $usersStmt = $pdo->query("
        SELECT id, name, email
        FROM users
        WHERE approved = 1
        ORDER BY name ASC
    ");

    $surveysStmt = $pdo->query("
        SELECT 
            surveys.id,
            surveys.assigned_user_id,
            surveys.title,
            surveys.status,
            surveys.created_at,
            users.name AS user_name,
            users.email AS user_email
        FROM surveys
        JOIN users ON users.id = surveys.assigned_user_id
        ORDER BY surveys.created_at DESC
    ");

    echo json_encode([
        "success" => true,
        "users" => $usersStmt->fetchAll(),
        "surveys" => $surveysStmt->fetchAll()
    ]);
    exit;
}

if ($method === "POST") {

    $input = json_decode(file_get_contents("php://input"), true);

    $title = trim($input["title"] ?? "");
    $assignedUserId = (int)($input["assignedUserId"] ?? 0);
    $questions = $input["questions"] ?? [];

    if (!$title || !$assignedUserId || !count($questions)) {
        echo json_encode([
            "success" => false,
            "message" => "Survey title, user and questions are required"
        ]);
        exit;
    }

    $pdo->beginTransaction();

    try {

        $stmt = $pdo->prepare("
            INSERT INTO surveys (title, assigned_user_id, status)
            VALUES (?, ?, 'pending')
        ");

        $stmt->execute([
            $title,
            $assignedUserId
        ]);

        $surveyId = $pdo->lastInsertId();

        $qStmt = $pdo->prepare("
    INSERT INTO survey_questions
    (survey_id, question_text, question_type, required, sort_order, chips)
    VALUES (?, ?, ?, ?, ?, ?)
");

        foreach ($questions as $index => $question) {

            $questionText = trim($question["text"] ?? "");
            $questionType = $question["type"] ?? "input";

            $chips = trim($question["chips"] ?? "");

$chipsArray = [];

if($chips){
    $chipsArray = array_filter(
        array_map("trim", explode(",", $chips))
    );
}

            if (!$questionText) {
                continue;
            }

            if (!in_array($questionType, ["input", "textarea"])) {
                $questionType = "input";
            }

            $qStmt->execute([
    $surveyId,
    $questionText,
    $questionType,
    1,
    $index + 1,
    json_encode($chipsArray)
]);
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Survey created successfully"
        ]);
        exit;

    } catch (Exception $e) {

        $pdo->rollBack();

        echo json_encode([
            "success" => false,
            "message" => "Failed to create survey"
        ]);
        exit;
    }
}

echo json_encode([
    "success" => false,
    "message" => "Invalid request"
]);