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
                        'Analyze the image and convert it into a detailed Mermaid.js diagram.

Return ONLY Mermaid syntax. No markdown or explanations.
Start with:
flowchart TD

Create a professional, information-dense diagram that looks like a polished infographic, not a simple chain of boxes.

CONTENT:
- Extract as much useful information from the image as possible.
- Include explanations, examples, definitions, conditions, and notes.
- Balance detail and readability.
- Do not make every piece of information its own node.
- Use a mixture of:
  - flowchart nodes for main steps
  - annotation/info boxes for extra details
  - subgraph compartments for related sections

LAYOUT:
- Use subgraph sections to organize related concepts.
- Add side information boxes connected with dashed arrows.
- Put supporting explanations, bullet lists, and notes in separate boxes near the relevant step.
- Create visual grouping like a textbook diagram.
- Keep the diagram compact and avoid excessive vertical scrolling.

NODE STYLES:

Main process:
A["Process Name<br/>• Step detail<br/>• Important point"]

Decision:
A{"Decision question"}

Start/end:
A(["Start / End"])

Information boxes:
A["Notes:<br/>• Explanation<br/>• Example<br/>• Key fact"]

Use dashed arrows for explanations:
A -.-> B

Use normal arrows for the main flow:
A --> B

TEXT:
- Use <br/> for line breaks.
- Use bullet points (•) and dashes (-) frequently.
- Put details outside main flow nodes when they are supporting information.
- Keep main nodes clean and readable.

MERMAID RULES:
- Every node needs a unique ID.
- Wrap all labels in double quotes.
- Never put raw [] or {} inside text.
- Only use {} for decision diamonds.
- Never put text after a closing bracket.
- Escape special characters.
- Keep syntax valid Mermaid.

STYLING:
Add classDef at the bottom:
- Green start/end nodes
- Blue process nodes
- Yellow decisions
- Purple data nodes
- Gray/white info boxes
- Rounded corners
- Clear borders

Apply styles with class statements.

Return ONLY Mermaid syntax.
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