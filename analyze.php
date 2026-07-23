<?php
// analyze.php
require_once 'db.php';

// Ensure response is returned as valid JSON
header('Content-Type: application/json');

// 1. Get image file path from CLI argument or default to "test.jpg"
$imagePath = $argv[1] ?? 'test.jpg';

if (!file_exists($imagePath)) {
    echo json_encode(['error' => "File '{$imagePath}' not found."]);
    exit;
}

// 2. Read image and encode as base64
$imageData = file_get_contents($imagePath);
$base64Image = base64_encode($imageData);
$mimeType = mime_content_type($imagePath);

// Paste your actual Gemini API Key here
$apiKey = 'AQ.Ab8RN6IFDksfKy0n1eh-5SErF_HP1AlNw3S4qBR8gwf1xZIl9A';

// 3. Prepare Gemini API Payload
$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Describe what you see in this image in detail.'
                ],
                [
                    'inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => $base64Image
                    ]
                ]
            ]
        ]
    ]
];

// 4. Send cURL request to Gemini API (using gemini-3.5-flash)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    exit;
}
// Note: curl_close($ch) is omitted here as it is deprecated in PHP 8.5 (and handled automatically)

$responseData = json_decode($response, true);

// Check for Gemini API errors
if (isset($responseData['error'])) {
    echo json_encode(['error' => 'Gemini API Error: ' . $responseData['error']['message']]);
    exit;
}

// Extract AI output text
$aiAnswer = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No response received from Gemini.';

// 5. Save to Database
try {
    $stmt = $pdo->prepare("INSERT INTO scan_logs (image_path, ai_response) VALUES (?, ?)");
    $stmt->execute([$imagePath, $aiAnswer]);

    // Output clean JSON response
    echo json_encode([
        'success' => true,
        'image_path' => $imagePath,
        'ai_response' => $aiAnswer
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
}
?>