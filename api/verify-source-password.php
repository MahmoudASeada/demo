<?php
require_once "db.php";
header("Content-Type: application/json");

$headers=getallheaders();
$token=str_replace("Bearer ","",$headers["Authorization"]??"");

$stmt=$pdo->prepare("SELECT id,sources_password FROM admins WHERE session_token=?");
$stmt->execute([$token]);

$admin=$stmt->fetch();

if(!$admin){

    echo json_encode([
        "success"=>false
    ]);

    exit;
}

$input=json_decode(file_get_contents("php://input"),true);

$password=$input["password"]??"";

if(password_verify($password,$admin["sources_password"])){

    echo json_encode([
        "success"=>true
    ]);

}else{

    echo json_encode([
        "success"=>false,
        "message"=>"Wrong password"
    ]);

}