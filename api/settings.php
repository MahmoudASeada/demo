<?php
require_once "db.php";
header("Content-Type: application/json");

$headers = getallheaders();
$token = str_replace("Bearer ","",$headers["Authorization"] ?? "");

$stmt = $pdo->prepare("SELECT id,sources_password FROM admins WHERE session_token=? LIMIT 1");
$stmt->execute([$token]);
$admin = $stmt->fetch();

if(!$admin){
    echo json_encode([
        "success"=>false,
        "message"=>"Unauthorized"
    ]);
    exit;
}

if($_SERVER["REQUEST_METHOD"]=="GET"){

    echo json_encode([
        "success"=>true,
        "hasPassword"=>!empty($admin["sources_password"])
    ]);
    exit;
}

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $input = json_decode(file_get_contents("php://input"),true);

    $current = trim($input["current_password"] ?? "");
    $new = trim($input["new_password"] ?? "");
    $confirm = trim($input["confirm_password"] ?? "");

    if(strlen($new) < 4){
        echo json_encode([
            "success"=>false,
            "message"=>"Password must be at least 4 characters."
        ]);
        exit;
    }

    if($new !== $confirm){
        echo json_encode([
            "success"=>false,
            "message"=>"Passwords do not match."
        ]);
        exit;
    }

    // أول مرة
    if(empty($admin["sources_password"])){

        $hash = password_hash($new,PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE admins
            SET sources_password=?
            WHERE id=?
        ");

        $stmt->execute([
            $hash,
            $admin["id"]
        ]);

        echo json_encode([
            "success"=>true,
            "message"=>"Password saved successfully."
        ]);
        exit;
    }

    // تغيير كلمة المرور
    if(!password_verify($current,$admin["sources_password"])){

        echo json_encode([
            "success"=>false,
            "message"=>"Current password is incorrect."
        ]);
        exit;
    }

    $hash = password_hash($new,PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE admins
        SET sources_password=?
        WHERE id=?
    ");

    $stmt->execute([
        $hash,
        $admin["id"]
    ]);

    echo json_encode([
        "success"=>true,
        "message"=>"Password updated successfully."
    ]);
}