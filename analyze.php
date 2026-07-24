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
                        'Analyze the flowchart or diagram in this image and convert it into valid Mermaid.js syntax.

Return ONLY Mermaid code. Do not use markdown code blocks. Start directly with "flowchart TD".

Create a clean, professional-looking flowchart:
- Use descriptive node names.
- Use rounded rectangles ([Text]) for start/end and process steps.
- Use diamonds {Text} for decisions.
- Use cylinders [(Text)] for databases or stored data.
- Use document shapes where appropriate.
- Use clear directional arrows (-->).
- Label decision arrows with Yes/No when applicable.
- Organize the layout vertically from top to bottom.
- Keep spacing clean and avoid overlapping nodes.

Add Mermaid styling:
- Add classDef definitions for different node types.
- Use different colors for start/end, processes, decisions, databases, and important steps.
- Add rounded corners and readable text colors.
- Apply styles using class statements.
- Make the diagram visually appealing and presentation-ready.

If the image is not a flowchart, create a logical flowchart representing the content shown.

Example style format:
classDef startEnd fill:#90EE90,stroke:#333,stroke-width:2px,color:#000;
classDef process fill:#87CEEB,stroke:#333,stroke-width:2px,color:#000;
classDef decision fill:#FFD700,stroke:#333,stroke-width:2px,color:#000;
classDef database fill:#DDA0DD,stroke:#333,stroke-width:2px,color:#000;

Apply these styles to all appropriate nodes.

Return only the final Mermaid syntax. Do not add "```mermaid" at the start or "```" at the end.
IMPORTANT MERMAID RULES:
- Escape all curly braces that are part of normal text.
- Only use {Text} syntax for actual decision diamond nodes.
- Never put mathematical notation, sets, equations, or examples containing {} directly inside nodes.
- Replace curly braces in text with parentheses.
STRICT MERMAID SYNTAX RULES:
- Every node must be on its own line.
- Never put text after a closing node bracket ].
- Every arrow must connect complete nodes.
- Do not use brackets inside node text.
- Keep node text short (under 50 characters).
- Replace mathematical symbols with plain text.
- Escape special characters.
Always wrap node labels in double quotes.
Example:
A["Start Process"]
B{"Decision?"}
C(["End"])
- Add examples from the original image into the flowchart for better explanation, MAKE SURE THE FLOWCHART COVERS THE MAIN TOPIC WITHOUT
BEING TOO DENSE IN INFORMATION
- Make sure the arrows arent too long vertically, the flowchart shouldnt take up too much space, make it compact.'
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