<?php

$apiKey = "YOUR_API_KEY_HERE";

$url = "https://generativelanguage.googleapis.com/v1beta/models";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "x-goog-api-key: $apiKey"
    ]
]);

echo curl_exec($ch);