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
                        'Analyze the image and convert it into a beautiful, visually appealing Mermaid.js diagram.

Return ONLY Mermaid syntax. No markdown. No explanations.
Start exactly with:
flowchart TD

DESIGN GOAL:
Create a polished infographic-style diagram, similar to a professional textbook illustration or presentation slide.
Make it visually engaging with colors, sections, annotations, and balanced information density.

CONTENT:
- Extract important concepts, steps, explanations, examples, and relationships from the image.
- Do not oversimplify.
- Include a good amount of useful text, but keep it easy to read.
- Use a balance of main flow steps and supporting information.
- Put explanations and details into separate note/info boxes instead of making every main node huge.

LAYOUT:
- Use subgraph sections to create organized compartments.
- Arrange related ideas into groups.
- Use main arrows for the primary flow.
- Use dashed arrows for explanations, references, and supporting information.
- Add side panels, notes, and callout boxes when helpful.
- Keep the diagram compact while still detailed.
- Avoid a single long vertical chain.

NODE DESIGN:
Use different shapes:

Start/end:
A(["Start / End"])

Main process:
A["Title<br/>• Key point<br/>• Important detail"]

Decision:
A{"Question?<br/>Choose path"}

Data/database:
A[("Data<br/>• Stored information")]

Information/callout boxes:
A["💡 Notes:<br/>• Explanation<br/>• Example<br/>• Important fact"]

Use:
- <br/> for line breaks
- • for bullet points
- - for lists

VISUAL STYLE:
Make the diagram colorful and professional.

Create multiple style classes:
- Start/end: green gradient style
- Main steps: blue
- Important concepts: purple
- Decisions: yellow/orange
- Data: pink/purple
- Notes: light gray
- Warnings or key ideas: red accent

Add:
- Rounded corners
- Thick but clean borders
- Readable text colors
- Different colors for different categories
- Clear visual hierarchy

MERMAID RULES:
- Every node must have a unique ID.
- Wrap all node text in double quotes.
- Never put raw [] or {} inside labels.
- Only use {} for real decision diamonds.
- Never put text after a closing bracket.
- Replace mathematical symbols and unusual characters with words.
- Escape special characters.
- Ensure the output runs directly in Mermaid.js.

FINAL REQUIREMENT:
The result should look like a finished infographic diagram, not a basic flowchart.

Return only valid Mermaid syntax.
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