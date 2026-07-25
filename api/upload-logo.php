<?php
require_once "db.php";

header("Content-Type: application/json");

$headers=getallheaders();
$token=str_replace("Bearer ","",$headers["Authorization"] ?? "");

$stmt=$pdo->prepare("
SELECT id
FROM admins
WHERE session_token=?
LIMIT 1
");

$stmt->execute([$token]);

if(!$stmt->fetch()){
    exit(json_encode([
        "success"=>false,
        "message"=>"Unauthorized"
    ]));
}

if(empty($_FILES["logo"])){

    exit(json_encode([
        "success"=>false,
        "message"=>"Choose image"
    ]));
}

$tmp=$_FILES["logo"]["tmp_name"];

$ext=strtolower(pathinfo($_FILES["logo"]["name"],PATHINFO_EXTENSION));

$allowed=["png"];

if(!in_array($ext,$allowed)){

    exit(json_encode([
        "success"=>false,
        "message"=>"Invalid image"
    ]));
}

move_uploaded_file(
    $tmp,
    __DIR__."/../logo.png".$ext
);

echo json_encode([
    "success"=>true,
    "message"=>"Logo updated successfully"
]);