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

        "error" =>
            "GEMINI_API_KEY environment variable not set."

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



$mode =

    $input["mode"] ?? "flowchart";



// ----------------------------
// IMAGE HANDLING
// ----------------------------

$base64Image = null;

$mimeType =

    "image/jpeg";



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

    "uploads/captured_"

    . time()

    . "_"

    . uniqid()

    . ".jpg";



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

            __DIR__ .

            "/" .

            $imagePathToSave,

            base64_decode(

                $base64Image

            )

        );

    }

}



if (!$base64Image) {

    echo json_encode([

        "error" =>

            "No valid image provided."

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

Create a professional AI-designed Google Slides presentation.

The output should feel like a presentation made by a human designer using Canva or Beautiful.ai.

Return ONLY valid JSON.

No markdown.

No code fences.

No explanations outside JSON.

Analyze:

- Subject matter

- Important concepts

- Visual style

- Appropriate colors

- Best educational structure

- Possible diagrams

- Useful images

Design the presentation:

Choose:

- Color palette

- Background style

- Typography style

- Layout variety

- Visual hierarchy

- Decorative elements

- Image placement

Use this exact JSON format:

{

 "title":"Presentation title",

 "theme":{

   "name":"Theme name",

   "background":"HEX color",

   "primaryColor":"HEX color",

   "secondaryColor":"HEX color",

   "textColor":"HEX color",

   "style":"Modern, scientific, futuristic, minimal, creative, etc."

 },

 "slides":[

 {

  "layout":"title",

  "title":"Main title",

  "subtitle":"Subtitle",

  "visual":"Description of hero image, diagram, or graphic"

 },

 {

  "layout":"bullet",

  "title":"Slide title",

  "points":[

    "Point 1",

    "Point 2",

    "Point 3"

  ],

  "visual":"Image, diagram, chart, icon, or illustration"

 },

 {

  "layout":"image_text",

  "title":"Slide title",

  "text":[

    "Important explanation",

    "Additional information"

  ],

  "image":"Description of image to display"

 },

 {

  "layout":"comparison",

  "title":"Comparison title",

  "leftTitle":"Left side",

  "leftPoints":[

    "Point"

  ],

  "rightTitle":"Right side",

  "rightPoints":[

    "Point"

  ]

 },

 {

  "layout":"diagram",

  "title":"Diagram title",

  "steps":[

    "Step 1",

    "Step 2",

    "Step 3"

  ]

 }

 ],

 "images":[

 {

  "description":"Image or illustration that improves the slide",

  "slide":2

 }

 ]

}

Rules:

- Create 7-10 slides.

- First slide MUST use layout "title".

- Use different layouts throughout the presentation.

- Do not make every slide bullet points.

- Avoid large paragraphs.

- Keep text concise.

- Make slides visually balanced.

- Add diagrams, comparisons, and visual explanations when appropriate.

- Use the scanned image as inspiration.

- Suggest images that would improve understanding.

- Pick colors that match the topic.

Subject style examples:

Biology:

- green/blue palette

- scientific diagrams

- microscope imagery

History:

- parchment colors

- timeline layouts

- historical imagery

Technology:

- dark backgrounds

- neon accents

- futuristic graphics

Mathematics:

- geometric layouts

- clean colors

- equations and diagrams

Art:

- bold colors

- creative layouts

- visual emphasis

The final presentation should look professionally designed, not like plain notes.

Return JSON only.

';



} elseif ($mode === "form") {



    // --------------------------------------------------------
    // GENERAL PURPOSE GOOGLE FORM
    // --------------------------------------------------------

    $prompt = '

Analyze the image carefully.

Create a complete, general-purpose form based on the subject, event,
organization, activity, registration need, survey, application, or other
information shown in the image.

The form should NOT automatically be treated as a quiz.

Determine what kind of form would make the most sense from the image.

Examples of forms include:

- Registration forms
- Event sign-up forms
- Club interest forms
- Membership forms
- Surveys
- Feedback forms
- Applications
- Contact forms
- Volunteer forms
- Permission forms
- Information collection forms
- Interest surveys
- RSVP forms

Return ONLY valid JSON.

No markdown.

No code fences.

No explanations outside JSON.

Use exactly this general structure:

{
  "title":"Form title",
  "description":"Short description explaining the purpose of the form",
  "questions":[
    {
      "question":"Question text",
      "type":"short_answer",
      "required":true
    },
    {
      "question":"Question text",
      "type":"multiple_choice",
      "options":[
        "Option 1",
        "Option 2",
        "Option 3"
      ],
      "required":true
    }
  ]
}

Allowed question types:

- "short_answer"
- "paragraph"
- "multiple_choice"
- "checkboxes"
- "dropdown"
- "true_false"

Rules:

- Create the number of questions that makes sense for the form.
- Do NOT force the form to have exactly 10 questions.
- Questions should directly relate to the subject shown in the image.
- Infer useful questions from the information in the image.
- Include basic information questions when appropriate.
- Include name, email, contact information, or other identifying questions only when they make sense for the form.
- Use "short_answer" for names, emails, phone numbers, IDs, short responses, and similar information.
- Use "paragraph" for longer written responses.
- Use "multiple_choice" when the user should select exactly one option.
- Use "checkboxes" when the user may select multiple options.
- Use "dropdown" when there are many options and selecting one option makes sense.
- Use "true_false" when a yes/no-style or true/false question is appropriate.
- Every multiple_choice, checkboxes, or dropdown question MUST contain an "options" array.
- Options must be clear and useful.
- Do not create empty options.
- Do not use answer keys because this is a form, not necessarily a quiz.
- Do not include "answer" or "explanation" fields unless they are genuinely necessary.
- Set "required" to true for information that is normally necessary to complete the form.
- Set "required" to false for optional questions.
- Avoid asking for unnecessary personal information.
- Make the form practical and ready to use.
- Use concise question wording.
- Make the form appropriate for the audience implied by the image.
- If the image describes a club, organization, event, or activity, create questions that would actually be useful for registration or interest collection.
- If the image is clearly a survey or feedback context, create survey-style questions instead.
- If the image describes an application, create appropriate application questions.
- If the image describes an event, include relevant registration or RSVP information.
- If the image does not clearly specify a particular form type, create a sensible general information/interest form based on the image.
- Return valid JSON only.

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

                    "text" =>

                        $prompt

                ],

                [

                    "inlineData" => [

                        "mimeType" =>

                            $mimeType,

                        "data" =>

                            $base64Image

                    ]

                ]

            ]

        ]

    ]

];





$ch =

    curl_init($url);



curl_setopt_array($ch, [

    CURLOPT_RETURNTRANSFER =>

        true,

    CURLOPT_POST =>

        true,

    CURLOPT_POSTFIELDS =>

        json_encode($payload),

    CURLOPT_HTTPHEADER => [

        "Content-Type: application/json",

        "X-goog-api-key: " . $apiKey

    ],

    CURLOPT_TIMEOUT =>

        60

]);





$response =

    curl_exec($ch);





if ($response === false) {

    echo json_encode([

        "error" =>

            "cURL Error: " .

            curl_error($ch)

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





if ($httpCode != 200) {

    echo json_encode([

        "error" =>

            "Gemini API Error",

        "http_code" =>

            $httpCode,

        "response" =>

            $responseData

    ], JSON_PRETTY_PRINT);



    exit;

}





// ----------------------------
// GET AI RESPONSE
// ----------------------------

$aiAnswer =

    $responseData

    ["candidates"]

    [0]

    ["content"]

    ["parts"]

    [0]

    ["text"]

    ?? "";





if (!$aiAnswer) {

    echo json_encode([

        "error" =>

            "Gemini returned empty response",

        "response" =>

            $responseData

    ]);

    exit;

}





// ----------------------------
// CLEAN RESPONSE
// ----------------------------

$aiAnswer =

    preg_replace(

        '/```(?:json|mermaid)?/i',

        '',

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
// FIX JSON OUTPUT MODES
// ----------------------------

if (

    $mode === "quiz" ||

    $mode === "flashcards" ||

    $mode === "presentation" ||

    $mode === "form"

) {



    $decoded =

        json_decode(

            $aiAnswer,

            true

        );



    if (

        json_last_error() !==

        JSON_ERROR_NONE

    ) {

        echo json_encode([

            "error" =>

                "AI did not return valid JSON",

            "json_error" =>

                json_last_error_msg(),

            "raw_response" =>

                $aiAnswer

        ]);

        exit;

    }



    $aiAnswer =

        json_encode(

            $decoded,

            JSON_UNESCAPED_UNICODE

        );

}





// ----------------------------
// GENERATE ITEM NAME
// ----------------------------

if ($mode === "presentation") {

    $decodedPresentation =

        json_decode(

            $aiAnswer,

            true

        );



    $itemName =

        $decodedPresentation["title"]

        ?? "Untitled Presentation";



} elseif ($mode === "form") {

    $decodedForm =

        json_decode(

            $aiAnswer,

            true

        );



    $itemName =

        $decodedForm["title"]

        ?? "Untitled Form";



} elseif ($mode === "quiz") {

    $itemName =

        "Quiz - " .

        date("M j, Y g:i A");



} elseif ($mode === "flashcards") {

    $itemName =

        "Flashcards - " .

        date("M j, Y g:i A");



} elseif ($mode === "flowchart") {

    $itemName =

        "Flowchart - " .

        date("M j, Y g:i A");



} else {

    $itemName =

        "Study Item - " .

        date("M j, Y g:i A");

}





// ----------------------------
// SAVE TO generated_items
// ----------------------------

try {

    $stmt =

        $pdo->prepare(

            "

            INSERT INTO generated_items

            (

                name,

                type,

                content,

                presentation_url,

                user_id

            )

            VALUES

            (

                ?,

                ?,

                ?,

                NULL,

                ?

            )

            "

        );



    $stmt->execute([

        $itemName,

        $mode,

        $aiAnswer,

        $_SESSION["user_id"] ?? null

    ]);



    $generatedItemId =

        $pdo->lastInsertId();



} catch (PDOException $e) {

    echo json_encode([

        "error" =>

            "Generated item database error: "

            . $e->getMessage()

    ]);

    exit;

}





// ----------------------------
// OUTPUT
// ----------------------------

echo json_encode([

    "success" =>

        true,

    "mode" =>

        $mode,

    "image_path" =>

        $imagePathToSave,

    "ai_response" =>

        $aiAnswer,

    "item_id" =>

        $generatedItemId,

    "item_name" =>

        $itemName

]);

?>