<?php

require_once 'db.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------

$apiKey = getenv('GEMINI_API_KEY');

if (!$apiKey) {

    echo json_encode([
        "error" => "GEMINI_API_KEY environment variable not set."
    ]);

    exit;

}



// ----------------------------
// READ INPUT
// ----------------------------

$input = json_decode(
    file_get_contents("php://input"),
    true
);


$mode = $input['mode'] ?? "flowchart";



// ----------------------------
// IMAGE HANDLING
// ----------------------------

$base64Image = null;

$mimeType = "image/jpeg";


$uploadDir = __DIR__ . "/uploads";


if (!is_dir($uploadDir)) {

    mkdir($uploadDir, 0755, true);

}


$imagePathToSave =
    "uploads/captured_" . time() . ".jpg";



if (!empty($input['image'])) {


    if (
        preg_match(
            '/^data:(image\/[\w.+-]+);base64,(.*)$/',
            $input['image'],
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
// CREATE PROMPT
// ----------------------------


if ($mode === "flowchart") {


    $prompt = '

Analyze the image and convert it into a highly visual Mermaid.js infographic diagram.

Return ONLY Mermaid syntax.

Start with:

flowchart TD


Create a professional educational infographic.

Include:
- Main flow boxes
- Supporting explanations
- Side annotations
- Callout notes
- Examples
- Definitions
- Relationships
- Subgraph sections


Use:

Main nodes:
A["Title<br/>• Point<br/>• Detail"]


Side notes:
B["Explanation:<br/>• Example"]


Use dashed arrows for explanations.


Visual design:
- Blue main concepts
- Purple important ideas
- Green start/end
- Yellow decisions
- Gray notes


Rules:
- Every node must have a unique ID
- Every label must be inside quotes
- Use <br/> for line breaks
- No markdown
- No explanations
- Return only Mermaid code

';


} elseif ($mode === "quiz") {


    $prompt = '

Analyze the image.

Create a quiz based on the information.

Return ONLY the quiz text.


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
- Important concepts
- Applications
- Understanding questions

';


} else {


    $prompt = '

Analyze the image.

Create study flashcards.

Return ONLY flashcards.


Create 15 flashcards.


Format:


FLASHCARD 1


FRONT:
Question or term


BACK:
Definition or explanation


Include:
- Important terms
- Definitions
- Processes
- Examples
- Key ideas

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
// ----------------------------
// SEND GEMINI REQUEST
// ----------------------------


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

        "error" => "cURL Error: " . curl_error($ch)

    ]);


    exit;

}



$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);



// curl_close removed because PHP 8.5 deprecates it



$responseData = json_decode(
    $response,
    true
);





if ($httpCode != 200) {


    echo json_encode([

        "error" => "Gemini API Error",

        "http_code" => $httpCode,

        "response" => $responseData

    ]);


    exit;

}





// ----------------------------
// GET AI RESPONSE
// ----------------------------


$aiAnswer =

    $responseData['candidates'][0]['content']['parts'][0]['text']

    ?? "No response";





// Remove markdown fences if Gemini adds them

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
// SAVE TO DATABASE
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




    echo json_encode([


        "success" => true,


        "mode" => $mode,


        "image_path" => $imagePathToSave,


        "ai_response" => $aiAnswer


    ]);



} catch (PDOException $e) {


    echo json_encode([


        "error" => "Database Error: " . $e->getMessage()


    ]);



}


?>