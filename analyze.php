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
                        'Analyze the image and convert it into a highly visual Mermaid.js infographic diagram.

Return ONLY Mermaid syntax.
No markdown. No explanations.
Start with:
flowchart TD

GOAL:
Create a polished, colorful educational diagram that looks like a professional infographic, not a basic flowchart.

The diagram should contain:
- Main flow boxes
- Supporting text sections
- Side annotations
- Callout notes
- Labels and explanations outside the main flow
- Organized compartments
- Visual hierarchy

CONTENT:
- Extract detailed information from the image.
- Include important explanations, examples, definitions, and relationships.
- Use a balanced amount of text.
- Do not make every detail a main flow node.
- Keep the main path simple and place extra information around it.

LAYOUT:
- Use subgraph sections as visual compartments.
- Add separate information boxes near related nodes.
- Connect explanation boxes using dashed arrows.
- Use arrow labels for additional text when useful.
- Use side notes and callouts instead of putting everything inside main boxes.
- Make it feel like a diagram from a textbook or presentation.
- Keep it compact and balanced.

TEXT PLACEMENT:
Use different types of text:

Main nodes:
A["Main Step<br/>• Important detail<br/>• Key information"]

Side annotations:
B["Explanation:<br/>• Additional context<br/>• Example<br/>• Important note"]

Arrow labels:
A -->|Reason / Explanation| B

Section titles:
Use subgraph titles to separate topics.

Use bullet points and dashes frequently:
• Point one
• Point two
- Detail
- Example

SHAPES:
Start/end:
A(["Start"])

Process:
A["Process"]

Decision:
A{"Decision"}

Database:
A[("Stored Data")]

Important note:
A["★ Key Idea:<br/>• Explanation"]

VISUAL DESIGN:
Make it colorful and presentation-ready.

Create style classes:
- Start/end: green
- Main process: blue
- Secondary information: light blue
- Important concepts: purple
- Decisions: yellow/orange
- Data: pink
- Notes/callouts: gray
- Warnings: red

Add:
- Rounded corners
- Strong borders
- Different colors for categories
- Clean spacing
- Clear hierarchy

MERMAID RULES:
- Every node must have a unique ID.
- Every node label must be inside double quotes.
- Never use raw brackets or braces inside text.
- Only use curly braces for decision diamonds.
- Never put text after closing brackets.
- Use <br/> for line breaks.
- Replace unsupported symbols with plain text.
- Make sure the Mermaid code runs without errors.

IMPORTANT:
Do not create only a vertical chain of boxes.
Create a visually rich diagram with surrounding annotations, callouts, compartments, and supporting text.

Return only Mermaid syntax.
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