<?php

require_once 'db.php';

header('Content-Type: application/json');


// ----------------------------
// API KEY
// ----------------------------

$apiKey = getenv("GEMINI_API_KEY");


if (!$apiKey) {


    echo json_encode([

        "error" => "Gemini API key missing"

    ]);


    exit;


}




// ----------------------------
// READ INPUT
// ----------------------------


$input =
    json_decode(
        file_get_contents("php://input"),
        true
    );



$mode =
    $input["mode"] ?? "flowchart";






// ----------------------------
// IMAGE PROCESSING
// ----------------------------


$base64Image = null;

$mimeType = "image/jpeg";



$uploadDir =
    __DIR__ . "/uploads/";



if (!is_dir($uploadDir)) {


    mkdir(
        $uploadDir,
        0755,
        true
    );


}



$imagePathToSave =

    "uploads/captured_" .
    time() .
    ".jpg";







if (!empty($input["image"])) {



    if (
        preg_match(
            '/^data:(image\/[\w.+-]+);base64,(.*)$/',
            $input["image"],
            $matches
        )
    ) {


        $mimeType =
            $matches[1];


        $base64Image =
            $matches[2];



        file_put_contents(

            __DIR__ . "/" . $imagePathToSave,

            base64_decode($base64Image)

        );


    }


}





if (!$base64Image) {


    echo json_encode([

        "error" => "No image received"

    ]);


    exit;


}







// ----------------------------
// GEMINI PROMPTS
// ----------------------------


if ($mode === "flowchart") {



    $prompt = <<<PROMPT

Analyze the image and convert it into a highly visual Mermaid.js infographic.

Return ONLY Mermaid code.

Start with:

flowchart TD


Requirements:

- Create a detailed educational diagram
- Include main concepts
- Include supporting notes
- Include examples
- Include explanations
- Use subgraphs when useful
- Make it look like a textbook infographic


Rules:

- Every node needs a unique ID
- Every label must be inside quotes
- Use <br/> for line breaks
- Do not include markdown
- Return only Mermaid syntax
PROMPT;



} elseif ($mode === "quiz") {



    $prompt = <<<PROMPT

Analyze the image.

Create an interactive multiple choice quiz.

Return ONLY valid JSON.

Use exactly this structure:


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
- answer is the index of the correct choice
- Questions should test understanding
- Include definitions and applications

Do not include markdown.
PROMPT;



} else {



    $prompt = <<<PROMPT

Analyze the image.

Create Quizlet-style flashcards.

Return ONLY valid JSON.

Use exactly this structure:


{
 "cards":[
  {
   "front":"Question or term",
   "back":"Answer or explanation"
  }
 ]
}



Rules:

- Create 15 cards
- Include important concepts
- Include definitions
- Include examples when helpful

Do not include markdown.
PROMPT;



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
// SEND REQUEST TO GEMINI
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





$response =
    curl_exec($ch);





if ($response === false) {


    echo json_encode([

        "error" => curl_error($ch)

    ]);


    exit;


}




$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );



// Do not use curl_close()
// PHP 8.5 deprecates it





$responseData =
    json_decode(
        $response,
        true
    );






if ($httpCode !== 200) {


    echo json_encode([


        "error" => "Gemini API Error",


        "code" => $httpCode,


        "details" => $responseData


    ]);


    exit;


}







// ----------------------------
// EXTRACT GEMINI RESPONSE
// ----------------------------


$aiAnswer =

    $responseData
    ["candidates"][0]
    ["content"]
    ["parts"][0]
    ["text"]

    ?? "";





$aiAnswer =
    trim($aiAnswer);






// Remove markdown if Gemini adds it

$aiAnswer =
    preg_replace(
        "/```(?:json|mermaid)?/i",
        "",
        $aiAnswer
    );



$aiAnswer =
    str_replace(
        "```",
        "",
        $aiAnswer
    );



$aiAnswer =
    trim($aiAnswer);






// ----------------------------
// CLEAN JSON FOR QUIZ/CARDS
// ----------------------------


if ($mode !== "flowchart") {


    $json =
        json_decode(
            $aiAnswer,
            true
        );



    if ($json === null) {


        echo json_encode([

            "error" => "Gemini returned invalid JSON",

            "raw" => $aiAnswer

        ]);

        exit;


    }



    $aiAnswer =
        json_encode($json);



}








// ----------------------------
// SAVE TO DATABASE
// ----------------------------


try {



    $stmt =
        $pdo->prepare(

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


        "error" => "Database error: " . $e->getMessage()


    ]);



}



?>