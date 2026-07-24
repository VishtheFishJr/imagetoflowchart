<?php

$apiKey = "AQ.Ab8RN6LQwwMMPp9NQdqOsUvG1zSm-DoUCc85bJ0FDiEj7q5Ceg";

$url = "https://generativelanguage.googleapis.com/v1beta/models";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "x-goog-api-key: $apiKey"
    ]
]);

echo curl_exec($ch);