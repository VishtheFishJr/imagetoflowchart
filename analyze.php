<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------
define('GEMINI_API_KEY', 'AQ.Ab8RN6IxW4sfxn7I-fZMwKXxlurU1PBvfq-b8AAGMZJDePmHzA');
$apiKey = trim(GEMINI_API_KEY);

if (!$apiKey) {
    echo json_encode([
        "error" => "GEMINI_API_KEY not found."
    ]);
    exit;
}


// ----------------------------
// TEST GEMINI REQUEST
// ----------------------------

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";

$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "Say hello."
                ]
            ]
        ]
    ]
];


$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-goog-api-key: " . $apiKey
    ],
    CURLOPT_TIMEOUT => 60
]);


$response = curl_exec($ch);


if ($response === false) {

    echo json_encode([
        "error" => curl_error($ch)
    ]);

    exit;
}


$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);



if ($httpCode !== 200) {

    echo json_encode([
        "success" => false,
        "status" => $httpCode,
        "response" => json_decode($response, true),
        "raw" => $response
    ], JSON_PRETTY_PRINT);

    exit;
}



$responseData = json_decode($response, true);


echo json_encode([
    "success" => true,
    "message" => "Gemini API works",
    "response" => $responseData
], JSON_PRETTY_PRINT);

exit;

?>