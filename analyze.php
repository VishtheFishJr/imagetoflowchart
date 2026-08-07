<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------
// $apiKey = "my-key";

'
if (!$apiKey) {

    echo json_encode([
        "error" => "GEMINI_API_KEY environment variable not set."
    ]);

    exit;

}
'

// ----------------------------
// READ INPUT
// ----------------------------

$input = json_decode(
    file_get_contents("php://input"),
    true
);


$mode = $input["mode"] ?? "flowchart";


// ----------------------------
// IMAGE HANDLING
// ----------------------------

$base64Image = null;
$mimeType = "image/jpeg";

$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


$imagePathToSave =
    "uploads/captured_" . time() . ".jpg";


if (!empty($input["image"])) {

    if (
        preg_match(
            '/^data:(image\/[\w.+-]+);base64,(.*)$/',
            $input["image"],
            $matches
        )
    ) {

        $mimeType = $matches[1];

        $base64Image = $matches[2];


        file_put_contents(
            __DIR__ . "/" . $imagePathToSave,
            base64_decode($base64Image)
        );

    }

}


if (!$base64Image) {

    echo json_encode([
        "error" => "No valid image provided."
    ]);

    exit;

}



// ----------------------------
// PROMPTS
// ----------------------------

if ($mode === "flowchart") {


    $prompt = '

Analyze the image and convert it into a highly visual Mermaid.js infographic diagram.

Return ONLY Mermaid syntax.
No markdown. No explanations.

Start with:

flowchart TD

Create a polished educational infographic.

Include:

- Main concepts
- Supporting explanations
- Examples
- Definitions
- Relationships
- Side notes
- Callouts

Layout:

- Use subgraphs as visual sections
- Add supporting information boxes
- Use dashed arrows for explanations
- Keep the main flow simple
- Add details around the main path

Text:

Use:
- Bullet points
- Definitions
- Examples
- Important notes

Use <br/> for line breaks.

Shapes:

Start/end:
A(["Start"])

Process:
A["Process"]

Decision:
A{"Decision"}

Database:
A[("Data")]


Rules:

- Every node needs a unique ID
- Every label must be inside quotes
- No markdown
- Return only Mermaid code
- Make it visually rich and presentation ready

';


} elseif ($mode === "quiz") {


    $prompt = '

Analyze the image.

Create a multiple choice quiz.

Return ONLY text.

Create 10 questions.

Format:

QUESTION 1:
Question

A) Option
B) Option
C) Option
D) Option

ANSWER:
Correct answer

EXPLANATION:
Explanation


Include:
- Definitions
- Concepts
- Applications

';


} else {


    $prompt = '

Analyze the image.

Create Quizlet style flashcards.

Return ONLY text.

Create 15 flashcards.

Format:

FLASHCARD 1

FRONT:
Term or question

BACK:
Definition or explanation


Include:
- Important terms
- Definitions
- Examples
- Key ideas

';


}



// ----------------------------
// GEMINI REQUEST
// ----------------------------

$url =
    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";


$payload = [

    "contents" => [

        [

            "parts" => [

                [
                    "text" => $prompt
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

        "X-goog-api-key: " . $apiKey

    ],

    CURLOPT_TIMEOUT => 60

]);



$response = curl_exec($ch);



if ($response === false) {

    echo json_encode([

        "error" => "cURL Error: " . curl_error($ch)

    ]);

    exit;

}



$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);


curl_close($ch);



$responseData = json_decode(
    $response,
    true
);



if ($httpCode != 200) {


    echo json_encode([

        "error" => "Gemini API Error",

        "http_code" => $httpCode,

        "response" => $responseData

    ], JSON_PRETTY_PRINT);


    exit;

}



// ----------------------------
// GET RESPONSE
// ----------------------------


$aiAnswer =
    $responseData["candidates"][0]["content"]["parts"][0]["text"]
    ?? "No response";



$aiAnswer = preg_replace(
    '/```(?:mermaid)?/i',
    '',
    $aiAnswer
);


$aiAnswer = str_replace(
    "```",
    "",
    $aiAnswer
);


$aiAnswer = trim($aiAnswer);



// ----------------------------
// SAVE DATABASE
// ----------------------------


try {


    $stmt = $pdo->prepare(
        "
        INSERT INTO scan_logs
        (image_path, ai_response)
        VALUES (?, ?)
        "
    );


    $stmt->execute([

        $imagePathToSave,

        $aiAnswer

    ]);



} catch (PDOException $e) {


    echo json_encode([

        "error" => "Database Error: " . $e->getMessage()

    ]);

    exit;

}



// ----------------------------
// OUTPUT
// ----------------------------


echo json_encode([

    "success" => true,

    "mode" => $mode,

    "image_path" => $imagePathToSave,

    "ai_response" => $aiAnswer

]);

?>