<?php
require_once 'db.php';

header('Content-Type: application/json');

// Read API key from environment variable
$apiKey = getenv('GEMINI_API_KEY');

if (!$apiKey) {
    echo json_encode([
        'error' => 'GEMINI_API_KEY environment variable not set.'
    ]);
    exit;
}

// ----------------------------
// Read image
// ----------------------------

$input = json_decode(file_get_contents('php://input'), true);

$base64Image = null;
$mimeType = 'image/jpeg';

$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$imagePathToSave = 'uploads/captured_' . time() . '.jpg';

if (!empty($input['image'])) {

    if (preg_match('/^data:(image\/[\w.+-]+);base64,(.*)$/', $input['image'], $matches)) {

        $mimeType = $matches[1];
        $base64Image = $matches[2];

        file_put_contents(
            __DIR__ . '/' . $imagePathToSave,
            base64_decode($base64Image)
        );
    }

} else {

    if (php_sapi_name() === 'cli') {

        $imagePath = $argv[1] ?? 'test.jpg';

        if (file_exists($imagePath)) {
            $base64Image = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
            $imagePathToSave = $imagePath;
        }

    }

}

if (!$base64Image) {
    echo json_encode([
        'error' => 'No valid image provided.'
    ]);
    exit;
}

// ----------------------------
// Gemini Request
// ----------------------------

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent";

$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" =>
                        'Analyze the image and convert it into valid Mermaid.js syntax.

Return ONLY Mermaid code. No markdown, no explanations. Start with:
flowchart TD

Create a clean, compact, professional flowchart:
- Use descriptive but short node labels.
- Use rounded boxes for start/end: A(["Text"])
- Use rectangles for processes: A["Text"]
- Use diamonds for decisions: A{"Text"}
- Use cylinders for databases: A[("Text")]
- Connect every node with arrows.
- Label decision arrows Yes/No when needed.
- Keep the layout top-to-bottom and compact.
- Avoid long vertical spacing.
- Use <br/> for multiple lines.
- Use bullets (•) or dashes (-) for lists.
- Keep nodes detailed but not overcrowded.

Formatting rules:
- Every node must have a unique ID.
- Every node label must be inside double quotes.
- Never put text after a closing node bracket.
- Never use raw [] or {} inside node text.
- Replace mathematical symbols and special characters with plain text.
- Only use {} for actual decision diamonds.
- Keep node text under 50 characters when possible.

Add Mermaid styling at the bottom:
- Use classDef for start/end, processes, decisions, databases, and important nodes.
- Apply colors, borders, rounded corners, and readable text.

Example style:
classDef startEnd fill:#90EE90,stroke:#333,stroke-width:2px,color:#000;
classDef process fill:#87CEEB,stroke:#333,stroke-width:2px,color:#000;
classDef decision fill:#FFD700,stroke:#333,stroke-width:2px,color:#000;
classDef database fill:#DDA0DD,stroke:#333,stroke-width:2px,color:#000;

If the image is not a flowchart, create a logical flowchart representing the information shown.

Output only Mermaid syntax.
'
                ],
                [
                    "inlineData" => [
                        "mimeType" => $mimeType,
                        "data" => $base64Image
                    ]
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
        "x-goog-api-key: " . $apiKey
    ],
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);

if ($response === false) {

    echo json_encode([
        'error' => 'cURL Error: ' . curl_error($ch)
    ]);

    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$responseData = json_decode($response, true);

if ($httpCode != 200) {

    echo json_encode([
        'error' => 'Gemini API Error',
        'http_code' => $httpCode,
        'response' => $responseData
    ], JSON_PRETTY_PRINT);

    exit;
}

$aiAnswer =
    $responseData['candidates'][0]['content']['parts'][0]['text']
    ?? "flowchart TD\nA[No diagram detected]";

$aiAnswer = preg_replace('/```(?:mermaid)?/i', '', $aiAnswer);
$aiAnswer = str_replace('```', '', $aiAnswer);
$aiAnswer = trim($aiAnswer);

// ----------------------------
// Save to DB
// ----------------------------

try {

    $stmt = $pdo->prepare("
        INSERT INTO scan_logs
        (image_path, ai_response)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $imagePathToSave,
        $aiAnswer
    ]);

    echo json_encode([
        'success' => true,
        'image_path' => $imagePathToSave,
        'ai_response' => $aiAnswer
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'error' => 'Database Error: ' . $e->getMessage()
    ]);

}