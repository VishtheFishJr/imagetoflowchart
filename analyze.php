<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------

$apiKey = "AQ.Ab8RN6KqPNv_bG0ujCRwUIERsaOaQkxbHooydz-KwAS1HcT2Fg";
if (!$apiKey) {
    echo json_encode(["error" => "GEMINI_API_KEY environment variable not set."]);
    exit;
}


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

Start with:

flowchart TD

Create a professional educational infographic.

Include:
- Main concepts
- Supporting explanations
- Examples
- Definitions
- Relationships
- Side notes
- Callouts

Layout:
- Use subgraphs
- Use supporting boxes
- Use dashed arrows
- Create visual hierarchy

Rules:
- Every node needs a unique ID
- Labels must be inside quotes
- Use <br/> for line breaks
- No markdown
- Return only Mermaid code

';



} elseif ($mode === "quiz") {


    $prompt = '

Analyze the image.

Create a multiple choice quiz.

Return ONLY valid JSON.

Format:

{
"questions":[
{
"question":"",
"choices":["","","",""],
"answer":0,
"explanation":""
}
]
}

Rules:
- Create 10 questions
- Test understanding
- Include definitions and applications

';



} elseif ($mode === "presentation") {


    $prompt = '

Analyze the image.

Create an educational Google Slides presentation.

Return ONLY valid JSON.

Use exactly this format:

{
"title":"Presentation title",
"slides":[
{
"layout":"title",
"title":"Slide title",
"subtitle":"Subtitle"
},
{
"layout":"bullet",
"title":"Slide title",
"points":[
"Point one",
"Point two",
"Point three"
]
}
]
}

Rules:

- Create 6-8 slides
- First slide must use layout "title"
- Remaining slides use layout "bullet"
- Keep text concise
- Include definitions
- Include examples
- Include important concepts
- No markdown
- JSON only

';



} else {


    $prompt = '

Analyze the image.

Create Quizlet-style flashcards.

Return ONLY valid JSON.

Format:

{
"cards":[
{
"front":"",
"back":""
}
]
}

Rules:
- Create 15 cards
- Include concepts, definitions, examples

';


}



// ----------------------------
// GEMINI REQUEST
// ----------------------------


$url =
    "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent";



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

        "error" => curl_error($ch)

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

        "status" => $httpCode,

        "response" => $responseData

    ]);

    exit;

}



// ----------------------------
// GET RESPONSE
// ----------------------------


$aiAnswer =
    $responseData["candidates"][0]["content"]["parts"][0]["text"]
    ?? "";



$aiAnswer = preg_replace(
    '/```(?:json|mermaid)?/i',
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
// SAVE LOG
// ----------------------------


try {


    $stmt = $pdo->prepare(

        "
INSERT INTO scan_logs
(image_path, ai_response)
VALUES (?,?)
"

    );


    $stmt->execute([

        $imagePathToSave,

        $aiAnswer

    ]);


} catch (PDOException $e) {


    echo json_encode([

        "error" => $e->getMessage()

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