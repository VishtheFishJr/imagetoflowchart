<?php

$apiKey = "AQ.Ab8RN6LlBIyYLF8zy-X8-pAIcEsNIJUcK96_3o78KzGd-YuI4w";

$url = "https://generativelanguage.googleapis.com/v1beta/models";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "x-goog-api-key: $apiKey"
    ]
]);

echo curl_exec($ch);