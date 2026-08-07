<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------
$apiKey = getenv("GEMINI_API_KEY");


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
No markdown.
No explanations.

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

Rules:

- Every node needs a unique ID
- Every label must be inside quotes
- Return only Mermaid code
- Make it presentation ready

';


} elseif ($mode === "quiz") {


    $prompt = '

Analyze the image.

Create a 10 question multiple choice quiz.

Return ONLY valid JSON.

No markdown.
No code fences.
No explanations outside JSON.

Use exactly this format:

{
 "questions":[
  {
   "question":"Question text",
   "choices":[
    "Choice 1",
    "Choice 2",
    "Choice 3",
    "Choice 4"
   ],
   "answer":0,
   "explanation":"Explanation"
  }
 ]
}

Rules:

- Create exactly 10 questions.
- answer must be 0,1,2,or 3.
- Include definitions and applications.
- Make incorrect choices realistic.
- Return JSON only.

';


} elseif ($mode === "flashcards") {


    $prompt = '

Analyze the image.

Create 15 Quizlet style flashcards.

Return ONLY valid JSON.

No markdown.
No code fences.

Use exactly this format:

{
 "cards":[
  {
   "front":"Term or question",
   "back":"Definition or explanation"
  }
 ]
}

Rules:

- Create exactly 15 cards.
- Include important terms.
- Include definitions.
- Include examples.
- Make them useful for studying.
- Return JSON only.

';


} elseif ($mode === "presentation") {


    $prompt = '

Analyze the image.

Create an editable Google Slides presentation outline.

Return ONLY valid JSON.

No markdown.
No code fences.

Use exactly this format:

{
 "title":"Presentation title",
 "slides":[
  {
   "layout":"title",
   "title":"Title",
   "subtitle":"Subtitle"
  },
  {
   "layout":"bullet",
   "title":"Slide title",
   "points":[
    "Point 1",
    "Point 2",
    "Point 3"
   ]
  }
 ]
}

Rules:

- Create 6-10 slides.
- First slide must be title.
- Remaining slides should be bullet slides.
- Each bullet slide needs 3-6 points.
- Make it educational.
- Use only information from the image.
- Return JSON only.

';


}
// ----------------------------
// GEMINI REQUEST
// ----------------------------

$url =
    "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent";


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
// GET AI RESPONSE
// ----------------------------


$aiAnswer =
    $responseData["candidates"][0]["content"]["parts"][0]["text"]
    ?? "";



if (!$aiAnswer) {

    echo json_encode([

        "error" => "Gemini returned empty response",

        "response" => $responseData

    ]);

    exit;

}



// Remove markdown fences if Gemini adds them

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
// FIX JSON OUTPUT MODES
// ----------------------------

if (
    $mode === "quiz" ||
    $mode === "flashcards" ||
    $mode === "presentation"
) {


    $decoded = json_decode(
        $aiAnswer,
        true
    );


    if (json_last_error() !== JSON_ERROR_NONE) {


        echo json_encode([

            "error" => "AI did not return valid JSON",

            "json_error" => json_last_error_msg(),

            "raw_response" => $aiAnswer

        ]);

        exit;

    }



    // Make sure frontend receives clean JSON string
    $aiAnswer = json_encode(
        $decoded,
        JSON_UNESCAPED_UNICODE
    );


}



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