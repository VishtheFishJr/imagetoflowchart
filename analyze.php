<?php
// analyze.php
require_once 'db.php';

// Ensure response is returned as valid JSON
header('Content-Type: application/json');

// Paste your actual Gemini API Key here
$apiKey = 'AQ.Ab8RN6KqGuabgIWkSzh5fgf_cKxvLkAUL8TuiJQEyHYaNaRVUQ';

// 1. Read input: Supports both JSON POST requests (from web) and CLI args
$input = json_decode(file_get_contents('php://input'), true);

$base64Image = null;
$mimeType = 'image/jpeg';
$imagePathToSave = 'uploads/captured_' . time() . '.jpg';

if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

if (!empty($input['image'])) {
    // Process base64 data URL sent from webcam
    $dataUrl = $input['image'];
    if (preg_match('/^data:(image\/\w+);base64,/', $dataUrl, $type)) {
        $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $mimeType = strtolower($type[1]);
        $base64Image = $data;

        // Save copy to disk for database tracking
        file_put_contents($imagePathToSave, base64_decode($data));
    }
} else {
    // Fallback: Check CLI argument or local test file
    $imagePath = $argv[1] ?? 'test.jpg';
    if (file_exists($imagePath)) {
        $base64Image = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
        $imagePathToSave = $imagePath;
    }
}

if (!$base64Image) {
    echo json_encode(['error' => 'No valid image provided.']);
    exit;
}

// 2. Prepare Gemini API Payload with strict Mermaid prompt
$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Analyze the flowchart or process in this image and convert it into valid Mermaid.js syntax. Start directly with "graph TD" or "flowchart TD". Do not wrap it in markdown code blocks like ```mermaid. Do not include introductory or concluding text.'
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

// 3. Send cURL request to Gemini API (gemini-3.5-flash)
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

$responseData = json_decode($response, true);

// Check for Gemini API errors
if (isset($responseData['error'])) {
    echo json_encode(['error' => 'Gemini API Error: ' . $responseData['error']['message']]);
    exit;
}

// Extract AI output text
$aiAnswer = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'graph TD;' . "\n" . 'A[No diagram detected]';

// Clean out potential markdown code blocks if the AI includes them
$aiAnswer = preg_replace('/```(mermaid)?/i', '', $aiAnswer);
$aiAnswer = trim($aiAnswer);

// 4. Save to Database
try {
    $stmt = $pdo->prepare("INSERT INTO scan_logs (image_path, ai_response) VALUES (?, ?)");
    $stmt->execute([$imagePathToSave, $aiAnswer]);

    // Output clean JSON response
    echo json_encode([
        'success' => true,
        'image_path' => $imagePathToSave,
        'ai_response' => $aiAnswer
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
}
?>