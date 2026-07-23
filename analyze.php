<?php
// analyze.php
require_once 'db.php';

// 1. Get image file path from command-line argument or default to "test.jpg"
$imagePath = $argv[1] ?? 'test.jpg';

if (!file_exists($imagePath)) {
    die("Error: File '{$imagePath}' not found.\n");
}

echo "Encoding image...\n";
$imageData = file_get_contents($imagePath);
$base64Image = base64_encode($imageData);

// Detect image mime type (e.g., image/jpeg or image/png)
$mimeType = mime_content_type($imagePath);
$dataUrl = "data:{$mimeType};base64,{$base64Image}";

$apiKey = 'YOUR_OPENAI_API_KEY'; // Replace with your actual key

// 2. Prepare API payload
$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Describe what you see in this image in detail.'],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $dataUrl
                    ]
                ]
            ]
        ]
    ],
    'max_tokens' => 300
];

echo "Sending image to AI...\n";

// 3. Send cURL request to OpenAI
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);

if (isset($responseData['error'])) {
    die("API Error: " . $responseData['error']['message'] . "\n");
}

$aiAnswer = $responseData['choices'][0]['message']['content'] ?? 'No response received.';

echo "\n--- AI Response ---\n";
echo $aiAnswer . "\n\n";

// 4. Save to Database on Droplet
try {
    $stmt = $pdo->prepare("INSERT INTO scan_logs (image_path, ai_response) VALUES (?, ?)");
    $stmt->execute([$imagePath, $aiAnswer]);
    echo "Successfully logged to database!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>