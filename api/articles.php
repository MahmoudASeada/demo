<?php

header("Content-Type: application/json");

$query = '*[_type=="article" && active==true]|order(publishedDate desc){
  title,
  smallTitle,
  description,
  category,
  readTime,
  publishedDate,
  featured,
  buttonText,
  buttonLink,
  "imageUrl": image.asset->url
}';

$url = "https://0b1rlnsm.api.sanity.io/v2021-10-21/data/query/production?query=" . urlencode($query);

$response = file_get_contents($url);

if ($response === false) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch articles"
    ]);
    exit;
}

echo $response;