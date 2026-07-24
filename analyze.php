<?php
// analyze.php
require_once 'db.php';

// Ensure response is returned as valid JSON
header('Content-Type: application/json');

// Paste your actual Gemini API Key here
$apiKey = 'AQ.Ab8RN6LlBIyYLF8zy-X8-pAIcEsNIJUcK96_3o78KzGd-YuI4w';

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

// 2. Prepare Gemini API Payload
$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Analyze the flowchart or diagram in this image and convert it into valid Mermaid.js syntax. Start directly with "flowchart TD" or "graph TD". Use appropriate node shapes: rounded boxes ([Text]), decision diamonds {Text}, stadium shapes ([Start/End]), or cylindrical databases [(Database)]. Use clear arrows like --> or -->|Yes| or -->|No|. Do not wrap in markdown code blocks like ```mermaid. Do not include extra conversational text.'
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

// 3. Send cURL request to Gemini API
// Using single quotes and zero concatenation to completely prevent parsing bugs
$url = '[https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent](https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent)';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Safety fallbacks to prevent network/IPv6 rejection
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Pass the API key cleanly via headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . trim($apiKey)
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