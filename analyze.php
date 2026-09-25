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

- Every label must be inside quotes: ID["Label text"]

- NEVER use double quotes INSIDE label text. Use single quotes or plain text.

- Do NOT nest quotes like ID[""Text""] or ID["Text "Quote" Text"].

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

You are a world-class presentation designer creating a slide deck from a scanned image or document.

STEP 1 — ANALYZE THE IMAGE:
Look carefully at the image and identify:
- Subject matter and topic domain (science, history, business, math, literature, etc.)
- How information is organized: headers, subheadings, bullet lists, diagrams, tables, paragraphs
- Key concepts, definitions, examples, comparisons, processes, or data present
- Visual density: is it text-heavy, diagram-heavy, or mixed?
- The tone: academic, casual, professional, creative?

STEP 2 — DESIGN THE THEME:
Based on the subject, choose:
- A color palette that fits the topic (biology = green/teal, tech = dark/neon, history = warm/parchment, math = clean/minimal, art = bold/vibrant)
- A style (Modern, Scientific, Corporate, Creative, Academic, Futuristic, Minimal, Bold)
- Fonts appropriate for the subject

STEP 3 — DESIGN ADAPTIVE SLIDES:
Create 8-12 slides. DO NOT create the same layout repeatedly. Pick the best layout for each piece of content.

For content with a clear heading + multiple subtopics → use "hierarchical_bullet"
For content comparing two things → use "comparison"
For content that is a process or steps → use "diagram"
For content with a key statistic or fact → use "stats"
For an impactful quote or key definition → use "quote"
For a section transition or chapter break → use "section_break"
For content best shown with an image alongside text → use "image_text"
For content that is purely visual → use "image_full"
For three parallel concepts → use "three_column"
For the opening slide → use "title"
For a table of contents or agenda → use "agenda"

STEP 4 — APPLY FONT HIERARCHY:
Each slide must specify font sizes using these fields:
- "titleFontSize": size in points for the main slide title (24-44pt)
- "bodyFontSize": size in points for body text (14-22pt)
- "subtitleFontSize": size in points for subtitles or secondary headings (18-28pt)

Adjust font sizes based on content density:
- Few short points → larger fonts (bodyFontSize 20-22)
- Many dense points → smaller fonts (bodyFontSize 14-16)
- Title slides → large (titleFontSize 36-44)
- Section breaks → very large title (titleFontSize 38-44), minimal body text

STEP 5 — ADD SUBTOPICS:
For "hierarchical_bullet" layout, use the "points" field as an array of objects with this structure:
{
  "text": "Main point",
  "subtopics": ["Sub-detail 1", "Sub-detail 2"]
}
If a point has no subtopics, it can be a plain string.

Return ONLY valid JSON. No markdown. No code fences. No explanations outside JSON.

Use exactly this JSON structure:

{
  "title": "Presentation Title",
  "theme": {
    "name": "Theme Name",
    "background": "#HEX",
    "primaryColor": "#HEX",
    "secondaryColor": "#HEX",
    "accentColor": "#HEX",
    "textColor": "#HEX",
    "style": "Modern"
  },
  "slides": [
    {
      "layout": "title",
      "title": "Main Title",
      "subtitle": "Subtitle or description",
      "titleFontSize": 40,
      "subtitleFontSize": 22,
      "visual": "Description of a background or hero visual for this slide"
    },
    {
      "layout": "agenda",
      "title": "Agenda",
      "titleFontSize": 32,
      "bodyFontSize": 18,
      "items": ["Topic 1", "Topic 2", "Topic 3"]
    },
    {
      "layout": "section_break",
      "title": "Section Title",
      "subtitle": "Brief description of this section",
      "titleFontSize": 42,
      "subtitleFontSize": 20
    },
    {
      "layout": "hierarchical_bullet",
      "title": "Slide Title",
      "titleFontSize": 28,
      "bodyFontSize": 16,
      "points": [
        {
          "text": "Main Point 1",
          "subtopics": ["Sub-detail A", "Sub-detail B"]
        },
        "Simple point with no subtopics",
        {
          "text": "Main Point 2",
          "subtopics": ["Sub-detail C"]
        }
      ],
      "visual": "Description of an image to accompany this slide"
    },
    {
      "layout": "image_text",
      "title": "Slide Title",
      "titleFontSize": 28,
      "bodyFontSize": 16,
      "text": ["Key point 1", "Key point 2", "Key point 3"],
      "image": "Description of image to place on the right side",
      "imagePosition": "right"
    },
    {
      "layout": "comparison",
      "title": "Comparison Title",
      "titleFontSize": 28,
      "bodyFontSize": 15,
      "leftTitle": "Concept A",
      "leftPoints": ["Point 1", "Point 2"],
      "rightTitle": "Concept B",
      "rightPoints": ["Point 1", "Point 2"]
    },
    {
      "layout": "stats",
      "title": "Key Facts",
      "titleFontSize": 30,
      "bodyFontSize": 18,
      "stats": [
        { "value": "42%", "label": "Key statistic label" },
        { "value": "1865", "label": "Another key fact" },
        { "value": "3x", "label": "Third key stat" }
      ]
    },
    {
      "layout": "quote",
      "title": "Key Definition",
      "titleFontSize": 26,
      "bodyFontSize": 20,
      "quote": "The exact quote, definition, or key statement to highlight",
      "attribution": "Source, author, or textbook reference"
    },
    {
      "layout": "three_column",
      "title": "Three Concepts",
      "titleFontSize": 28,
      "bodyFontSize": 15,
      "columns": [
        { "heading": "Concept 1", "points": ["Detail A", "Detail B"] },
        { "heading": "Concept 2", "points": ["Detail C", "Detail D"] },
        { "heading": "Concept 3", "points": ["Detail E", "Detail F"] }
      ]
    },
    {
      "layout": "diagram",
      "title": "Process or Steps",
      "titleFontSize": 28,
      "bodyFontSize": 16,
      "steps": ["Step 1", "Step 2", "Step 3", "Step 4"],
      "visual": "Description of a diagram or process illustration"
    },
    {
      "layout": "image_full",
      "title": "Visual Concept",
      "titleFontSize": 26,
      "image": "Detailed description of the full-slide image to generate"
    }
  ]
}

CRITICAL RULES:
- First slide MUST be layout "title".
- Create 8-12 slides total.
- Use AT LEAST 5 different layout types throughout the deck.
- Never use the same layout more than 3 times in a row.
- Each slide must have titleFontSize specified.
- "hierarchical_bullet" points must use the subtopic object format for any point that has sub-details.
- Keep text concise: no wall of text, no paragraphs.
- Font sizes must reflect visual hierarchy (titles bigger than body).
- Return valid JSON only.

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

} elseif ($mode === "sheet" || $mode === "sheets" || $mode === "spreadsheet") {

    $prompt = '

Analyze the image carefully.

Convert the information, data, tables, lists, schedules, budgets, financial records, notes, or structured information shown in the image into a clean, well-organized Google Spreadsheet structure.

Interpret how to input the data into proper cells in an organized manner with clear column headers and structured rows.

Return ONLY valid JSON.

No markdown.

No code fences.

No explanations outside JSON.

Use exactly this JSON format:

{
  "title": "Descriptive Spreadsheet Title",
  "sheetName": "Sheet1",
  "data": [
    ["Header 1", "Header 2", "Header 3"],
    ["Row 1 Cell 1", "Row 1 Cell 2", "Row 1 Cell 3"],
    ["Row 2 Cell 1", "Row 2 Cell 2", "Row 2 Cell 3"]
  ]
}

Rules:
- "title": A clear title summarizing the spreadsheet contents based on the image.
- "sheetName": Name for the spreadsheet tab (e.g. "Sheet1" or a clear descriptive name).
- "data": A 2D array of strings representing rows and columns.
- The first row (index 0) MUST contain column headers (e.g., Name, Category, Date, Quantity, Amount, Status, Notes, etc.).
- Subsequent rows contain the interpreted data values from the image formatted cleanly.
- Ensure every row has entries matching the layout of the headers.
- Keep the table layout clear, accurate, and ready for a spreadsheet.
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



if ($mode === "flowchart") {

    if (preg_match('/(flowchart\s+[A-Za-z]+|graph\s+[A-Za-z]+)/i', $aiAnswer, $matches, PREG_OFFSET_CAPTURE)) {

        $aiAnswer = substr($aiAnswer, $matches[0][1]);

    }



    $lines = explode("\n", $aiAnswer);

    $fixedLines = [];



    foreach ($lines as $line) {

        $line = preg_replace_callback('/([A-Za-z0-9_-]+)\s*(\[\s*"+|\(\s*"+|\{\s*"+)((?:(?!\]|\)).)*?)("+\]|"\))/u', function($m) {

            $id = $m[1];

            $openChar = $m[2][0];

            $closeChar = substr($m[4], -1);

            $content = $m[3];



            $content = trim($content);

            $content = preg_replace('/^"+|"+$/', '', $content);

            $content = str_replace('"', "'", $content);



            return $id . $openChar . '"' . $content . '"' . $closeChar;

        }, $line);



        $line = preg_replace_callback('/(subgraph\s+[A-Za-z0-9_-]+\s*\[\s*"+)(.*?)("+\])/i', function($m) {

            $content = trim($m[2]);

            $content = preg_replace('/^"+|"+$/', '', $content);

            $content = str_replace('"', "'", $content);

            return $m[1] . $content . '"]';

        }, $line);



        $line = preg_replace_callback('/\|"(.*?)"\|/', function($m) {

            $content = str_replace('"', "'", $m[1]);

            return '|"' . $content . '"|';

        }, $line);



        $fixedLines[] = $line;

    }



    $aiAnswer = implode("\n", $fixedLines);

}





// ----------------------------
// FIX JSON OUTPUT MODES
// ----------------------------

if (

    $mode === "quiz" ||

    $mode === "flashcards" ||

    $mode === "presentation" ||

    $mode === "form" ||

    $mode === "sheet" ||

    $mode === "sheets" ||

    $mode === "spreadsheet"
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



} elseif ($mode === "sheet" || $mode === "sheets" || $mode === "spreadsheet") {

    $decodedSheet =

        json_decode(

            $aiAnswer,

            true

        );



    $itemName =

        $decodedSheet["title"]

        ?? "Untitled Spreadsheet";



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