<?php
require_once "db.php";

$token = $_GET["token"] ?? "";
$fileId = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM admins WHERE session_token=? LIMIT 1");
$stmt->execute([$token]);
$admin = $stmt->fetch();

if(!$admin){
  die("Unauthorized");
}

$stmt = $pdo->prepare("
  SELECT original_name,file_path,file_type
  FROM source_files
  WHERE id=? AND admin_id=?
");
$stmt->execute([$fileId, $admin["id"]]);
$file = $stmt->fetch();

if(!$file || !file_exists($file["file_path"])){
  die("File not found");
}

header("Content-Type: " . ($file["file_type"] ?: "application/octet-stream"));
header("Content-Disposition: attachment; filename=\"" . basename($file["original_name"]) . "\"");
header("Content-Length: " . filesize($file["file_path"]));

readfile($file["file_path"]);
exit;