<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once 'config.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------

$apiKey = GEMINI_API_KEY;


if (!$apiKey) {

    echo json_encode([
        "error" => "GEMINI_API_KEY environment variable not set."
    ]);

    exit;

}


// ----------------------------
// READ REQUEST
// ----------------------------

$input = json_decode(
    file_get_contents("php://input"),
    true
);


$mode = $input["mode"] ?? "flowchart";


// ----------------------------
// IMAGE PROCESSING
// ----------------------------

$base64Image = null;

$mimeType = "image/jpeg";


$uploadDir = __DIR__ . "/uploads/";


if (!is_dir($uploadDir)) {

    mkdir(
        $uploadDir,
        0755,
        true
    );

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
// GEMINI PROMPTS
// ----------------------------


if ($mode === "flowchart") {


    $prompt = <<<PROMPT

Analyze the image and convert it into a highly visual Mermaid.js infographic.

Return ONLY Mermaid syntax.

Start with:

flowchart TD

Create a professional educational diagram.

Include:
- Main concepts
- Supporting explanations
- Examples
- Definitions
- Side notes
- Relationships

Rules:
- Every node needs a unique ID
- Every label must be inside quotes
- Use <br/> for line breaks
- Use subgraphs when useful
- No markdown
- Return only Mermaid code
PROMPT;



} elseif ($mode === "quiz") {


    $prompt = <<<PROMPT

Analyze the image.

Create a multiple choice quiz.

Return ONLY valid JSON.

Use this structure:

{
 "questions":[
  {
   "question":"Question text",
   "choices":[
    "Choice A",
    "Choice B",
    "Choice C",
    "Choice D"
   ],
   "answer":0,
   "explanation":"Explanation"
  }
 ]
}

Rules:

- Create 10 questions
- answer is the index
- Test understanding
- Include definitions and applications
PROMPT;



} elseif ($mode === "flashcards") {


    $prompt = <<<PROMPT

Analyze the image.

Create Quizlet-style flashcards.

Return ONLY valid JSON.

Structure:

{
 "cards":[
  {
   "front":"Term or question",
   "back":"Definition or explanation"
  }
 ]
}

Rules:

- Create 15 cards
- Include important concepts
- Include definitions
- Include examples
PROMPT;



} elseif ($mode === "presentation") {


    $prompt = <<<PROMPT

Analyze the image.

Create a professional educational slide presentation.

Return ONLY valid JSON.

The output will be converted into editable Google Slides.

Use this exact format:

{
"title":"Presentation title",
"slides":[
 {
  "layout":"title",
  "title":"Slide title",
  "subtitle":"Subtitle text"
 },
 {
  "layout":"bullet",
  "title":"Topic title",
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
- Other slides should use layout "bullet"
- Make slides concise
- Use educational wording
- Include examples
- Include important definitions
- Do not include markdown
- Return JSON only
PROMPT;



}
// ----------------------------
// GEMINI API REQUEST
// ----------------------------


$url =

    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";



//d

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


        "x-goog-api-key: " . $apiKey


    ],


    CURLOPT_TIMEOUT => 60


]);



curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);





if ($response === false) {


    echo json_encode([

        "error" => "Curl Error: " . curl_error($ch)

    ]);


    exit;


}





$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );



curl_close($ch);





$responseData =
    json_decode(
        $response,
        true
    );





if ($httpCode !== 200) {

    header("Content-Type: application/json");

    die(json_encode([
        "success" => false,
        "status" => $httpCode,
        "response" => json_decode($response, true),
        "raw" => $response
    ], JSON_PRETTY_PRINT));
}








// ----------------------------
// GET AI RESPONSE
// ----------------------------


$aiAnswer =

    $responseData["candidates"][0]["content"]["parts"][0]["text"]

    ?? "";





$aiAnswer = trim($aiAnswer);






// Remove markdown fences

$aiAnswer = preg_replace(

    "/```(?:json|mermaid)?/i",

    "",

    $aiAnswer

);



$aiAnswer = str_replace(

    "```",

    "",

    $aiAnswer

);



$aiAnswer = trim($aiAnswer);






// ----------------------------
// VALIDATE JSON OUTPUT
// ----------------------------
// Everything except flowcharts is JSON


if ($mode !== "flowchart") {


    $jsonCheck =
        json_decode(

            $aiAnswer,

            true

        );



    if ($jsonCheck === null) {


        echo json_encode([


            "error" => "Gemini returned invalid JSON",


            "raw" => $aiAnswer


        ]);


        exit;


    }





    $aiAnswer =
        json_encode(

            $jsonCheck,

            JSON_UNESCAPED_UNICODE

        );


}
// ----------------------------
// SAVE TO DATABASE
// ----------------------------


try {



    if ($mode === "flowchart") {



        $stmt = $pdo->prepare(

            "
            INSERT INTO image_logs
            (image_path, ai_response)
            VALUES (?,?)
            "

        );



        $stmt->execute([

            $imagePathToSave,

            $aiAnswer

        ]);



    } elseif ($mode === "quiz") {



        $stmt = $pdo->prepare(

            "
            INSERT INTO quiz_logs
            (image_path, quiz_data)
            VALUES (?,?)
            "

        );



        $stmt->execute([

            $imagePathToSave,

            $aiAnswer

        ]);



    } elseif ($mode === "flashcards") {



        $stmt = $pdo->prepare(

            "
            INSERT INTO flashcard_logs
            (image_path, flashcard_data)
            VALUES (?,?)
            "

        );



        $stmt->execute([

            $imagePathToSave,

            $aiAnswer

        ]);



    } elseif ($mode === "presentation") {



        // Only save if table exists
        // We will create this table next


        try {


            $stmt = $pdo->prepare(

                "
                INSERT INTO presentation_logs
                (image_path, presentation_data)
                VALUES (?,?)
                "

            );



            $stmt->execute([


                $imagePathToSave,


                $aiAnswer


            ]);



        } catch (PDOException $e) {

            // Ignore until table is created

        }


    }





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