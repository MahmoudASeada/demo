<?php

require_once "db.php";

header("Content-Type: application/json");

$headers = getallheaders();
$token = str_replace("Bearer ","",$headers["Authorization"] ?? "");

$stmt = $pdo->prepare("
SELECT id
FROM users
WHERE session_token=?
LIMIT 1
");

$stmt->execute([$token]);

$user = $stmt->fetch();

if(!$user){
    echo json_encode([
        "success"=>false,
        "message"=>"Unauthorized"
    ]);
    exit;
}

$stmt = $pdo->prepare("
SELECT
sf.id,
sf.original_name,
sf.file_size,
sf.uploaded_at

FROM user_source_files usf

JOIN source_files sf
ON sf.id=usf.file_id

WHERE usf.user_id=?

ORDER BY usf.assigned_at DESC
");

$stmt->execute([$user["id"]]);

echo json_encode([
    "success"=>true,
    "files"=>$stmt->fetchAll()
]);