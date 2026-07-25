<?php
require_once "db.php";
header("Content-Type: application/json");

$headers = getallheaders();
$token = str_replace("Bearer ","",$headers["Authorization"] ?? "");

$stmt = $pdo->prepare("SELECT id FROM admins WHERE session_token=? LIMIT 1");
$stmt->execute([$token]);
$admin=$stmt->fetch();

if(!$admin){
    echo json_encode([
        "success"=>false,
        "message"=>"Unauthorized"
    ]);
    exit;
}

if($_SERVER["REQUEST_METHOD"]=="GET"){

    $stmt=$pdo->prepare("
        SELECT sources_password
        FROM admins
        WHERE id=?
    ");

    $stmt->execute([$admin["id"]]);

    $row=$stmt->fetch();

    echo json_encode([
        "success"=>true,
        "hasPassword"=>!empty($row["sources_password"])
    ]);

    exit;
}

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $input=json_decode(file_get_contents("php://input"),true);

    // Verify Password
    if(($input["action"] ?? "") == "verify"){

        $stmt=$pdo->prepare("
            SELECT sources_password
            FROM admins
            WHERE id=?
        ");

        $stmt->execute([$admin["id"]]);

        $row=$stmt->fetch();

        if(
            $row &&
            password_verify($input["password"] ?? "", $row["sources_password"])
        ){

            echo json_encode([
                "success"=>true
            ]);

        }else{

            echo json_encode([
                "success"=>false,
                "message"=>"Wrong password"
            ]);

        }

        exit;
    }

    // Save Password
    $password=trim($input["password"]??"");

    if(strlen($password)<4){

        echo json_encode([
            "success"=>false,
            "message"=>"Minimum 4 characters"
        ]);
        exit;
    }

    $hash=password_hash($password,PASSWORD_DEFAULT);

    $stmt=$pdo->prepare("
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
        "message"=>"Password saved"
    ]);

    exit;
}