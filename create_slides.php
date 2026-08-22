<?php

require_once 'vendor/autoload.php';
require_once 'presentation_templates.php';


session_name("PHPSESSID");
session_start();


header("Content-Type: application/json");


// ----------------------------
// CHECK GOOGLE CONNECTION
// ----------------------------

if (!isset($_SESSION["google_token"])) {

    echo json_encode([
        "error" =>
            "Google account not connected"
    ]);

    exit;

}


$client =
    new Google_Client();


$client->setAuthConfig(
    "client_secret.json"
);


$client->setAccessToken(
    $_SESSION["google_token"]
);


if (
    $client->isAccessTokenExpired()
) {

    echo json_encode([
        "error" =>
            "Google token expired. Reconnect account."
    ]);

    exit;

}


$service =
    new Google_Service_Slides(
        $client
    );



// ----------------------------
// GEMINI API KEY
// ----------------------------

$apiKey =
    getenv("GEMINI_API_KEY");


if (!$apiKey) {

    echo json_encode([
        "error" =>
            "GEMINI_API_KEY environment variable not set."
    ]);

    exit;

}



// ----------------------------
// READ AI DATA
// ----------------------------

$data =
    json_decode(
        file_get_contents("php://input"),
        true
    );


if (!$data) {

    echo json_encode([
        "error" =>
            "Invalid JSON input"
    ]);

    exit;

}


$slidesData =
    $data["slides"] ?? [];



// ----------------------------
// THEME
// ----------------------------

$theme =
    $data["theme"] ?? [

        "name" =>
            "Modern",

        "background" =>
            "#FFFFFF",

        "primaryColor" =>
            "#2563EB",

        "secondaryColor" =>
            "#60A5FA",

        "textColor" =>
            "#111111",

        "style" =>
            "Modern"

    ];



// ----------------------------
// IMAGE GENERATION
// ----------------------------

function generateSlideImage(
    $description,
    $theme,
    $slideTitle = ""
) {

    global $apiKey;


    if (!$description) {

        return null;

    }


    $prompt =

        "Create an actual image, not a description.\n\n"

        . "Create a professional educational presentation graphic.\n\n"

        . "Subject: "
        . $slideTitle
        . "\n\n"

        . "Visual description:\n"
        . $description
        . "\n\n"

        . "Presentation style:\n"
        . ($theme["style"] ?? "modern")
        . "\n\n"

        . "Color palette:\n"
        . "Background: "
        . ($theme["background"] ?? "#FFFFFF")
        . "\nPrimary: "
        . ($theme["primaryColor"] ?? "#2563EB")
        . "\nSecondary: "
        . ($theme["secondaryColor"] ?? "#60A5FA")
        . "\n\n"

        . "Requirements:\n"
        . "- Generate the actual visual\n"
        . "- Do NOT return a text description\n"
        . "- Do NOT explain the image\n"
        . "- Professional educational graphic\n"
        . "- Clean composition\n"
        . "- Suitable for Google Slides\n"
        . "- 16:9 composition\n"
        . "- No unnecessary text\n"
        . "- Make the visual directly relevant to the subject\n"
        . "- Use the requested color palette\n";


    /*
     * KEEPING YOUR EXISTING GEMINI MODEL
     */

    $url =
        "https://generativelanguage.googleapis.com/v1/models/gemini-3.1-flash-image:generateContent";


    $payload = [

        "contents" => [

            [

                "parts" => [

                    [
                        "text" =>
                            $prompt
                    ]

                ]

            ]

        ],

        /*
         * Do not use the invalid
         * responseFormat settings.
         */

        "generationConfig" => [

            "responseModalities" => [

                "TEXT",

                "IMAGE"

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

            "X-goog-api-key: " .
            $apiKey

        ],

        CURLOPT_TIMEOUT =>
            120

    ]);


    $response =
        curl_exec($ch);


    file_put_contents(
        __DIR__ .
        "/gemini_image_debug.json",
        $response
    );


    if ($response === false) {

        error_log(
            "Gemini image cURL error: "
            . curl_error($ch)
        );

        curl_close($ch);

        return null;

    }


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    if ($httpCode !== 200) {

        error_log(
            "Gemini image generation failed: "
            . $httpCode
            . " "
            . $response
        );

        return null;

    }


    $responseData =
        json_decode(
            $response,
            true
        );


    if (!$responseData) {

        error_log(
            "Gemini returned invalid JSON."
        );

        return null;

    }


    $imageData = null;


    foreach (

        $responseData
        ["candidates"]
        [0]
        ["content"]
        ["parts"]
        ?? []

        as $part

    ) {


        if (
            isset(
            $part["text"]
        )
        ) {

            continue;

        }


        if (

            isset(
            $part["inlineData"]
        )

            &&

            isset(
            $part["inlineData"]["data"]
        )

        ) {

            $imageData =
                $part["inlineData"]["data"];

            break;

        }

    }


    if (!$imageData) {

        error_log(
            "Gemini returned no image data: "
            . $response
        );

        return null;

    }


    $imageBytes =
        base64_decode(
            $imageData,
            true
        );


    if (
        $imageBytes === false
    ) {

        error_log(
            "Could not decode Gemini image."
        );

        return null;

    }


    $uploadDir =
        __DIR__ .
        "/uploads/";


    if (!is_dir($uploadDir)) {

        mkdir(
            $uploadDir,
            0755,
            true
        );

    }


    $filename =
        "generated_slide_"
        . time()
        . "_"
        . uniqid()
        . ".png";


    $filePath =
        $uploadDir .
        $filename;


    $written =
        file_put_contents(
            $filePath,
            $imageBytes
        );


    if ($written === false) {

        error_log(
            "Could not save generated image: "
            . $filePath
        );

        return null;

    }


    $publicUrl =
        "https://vishthefishjr.me/uploads/"
        . $filename;


    return $publicUrl;

}



// ----------------------------
// CREATE PRESENTATION
// ----------------------------

$presentation =
    new Google_Service_Slides_Presentation();


$presentation->setTitle(
    $data["title"]
    ?? "AI Presentation"
);


$created =
    $service
        ->presentations
        ->create(
            $presentation
        );


$presentationId =
    $created->presentationId;



// ----------------------------
// BUILD REQUESTS
// ----------------------------

$requests = [];

$generatedImages = [];



foreach (
    $slidesData
    as $index => $slideData
) {


    $slideId =
        "slide_" . $index;


    $requests[] = [

        "createSlide" => [

            "objectId" =>
                $slideId,

            "slideLayoutReference" => [

                "predefinedLayout" =>
                    "BLANK"

            ]

        ]

    ];


    $layout =
        $slideData["layout"]
        ?? "bullet";



    // ----------------------------
    // TITLE
    // ----------------------------

    if ($layout === "title") {

        $requests =
            array_merge(

                $requests,

                titleSlide(

                    $slideId,

                    $slideData["title"]
                    ?? "",

                    $slideData["subtitle"]
                    ?? "",

                    $theme

                )

            );

    }



    // ----------------------------
    // BULLET
    // ----------------------------
    elseif ($layout === "bullet") {

        $requests =
            array_merge(

                $requests,

                bulletSlide(

                    $slideId,

                    $slideData["title"]
                    ?? "",

                    $slideData["points"]
                    ?? [],

                    $theme,

                    ""

                )

            );


        if (
            !empty(
            $slideData["visual"]
        )
        ) {

            $imageUrl =
                generateSlideImage(

                    $slideData["visual"],

                    $theme,

                    $slideData["title"]
                    ?? ""

                );


            if ($imageUrl) {

                $generatedImages[] =
                    $imageUrl;


                $requests =
                    array_merge(

                        $requests,

                        addImage(

                            $slideId,

                            $imageUrl,

                            4500000,

                            1100000,

                            2400000,

                            2200000

                        )

                    );

            }

        }

    }



    // ----------------------------
    // IMAGE + TEXT
    // ----------------------------
    elseif (
        $layout === "image_text"
    ) {

        $imageUrl = null;


        $visual =
            $slideData["image"]
            ?? $slideData["visual"]
            ?? "";


        if ($visual) {

            $imageUrl =
                generateSlideImage(

                    $visual,

                    $theme,

                    $slideData["title"]
                    ?? ""

                );


            if ($imageUrl) {

                $generatedImages[] =
                    $imageUrl;

            }

        }


        $requests =
            array_merge(

                $requests,

                imageTextSlide(

                    $slideId,

                    $slideData["title"]
                    ?? "",

                    $slideData["text"]
                    ?? $slideData["points"]
                    ?? [],

                    $visual,

                    $theme,

                    $imageUrl

                )

            );

    }



    // ----------------------------
    // COMPARISON
    // ----------------------------
    elseif (
        $layout === "comparison"
    ) {

        $requests =
            array_merge(

                $requests,

                comparisonSlide(

                    $slideId,

                    $slideData,

                    $theme

                )

            );

    }



    // ----------------------------
    // TIMELINE
    // ----------------------------
    elseif (
        $layout === "timeline"
    ) {

        $requests =
            array_merge(

                $requests,

                timelineSlide(

                    $slideId,

                    $slideData,

                    $theme

                )

            );

    }



    // ----------------------------
    // DIAGRAM
    // ----------------------------
    elseif (
        $layout === "diagram"
    ) {

        $steps =
            $slideData["steps"]
            ?? [];


        $text =
            ($slideData["title"]
                ?? "Diagram")
            . "\n\n";


        foreach (
            $steps
            as $stepIndex =>
            $step
        ) {

            $text .=
                ($stepIndex + 1)
                . ". "
                . $step
                . "\n\n";

        }


        $requests =
            array_merge(

                $requests,

                createTextBox(

                    $slideId,

                    $text,

                    700000,

                    900000,

                    6500000,

                    4000000,

                    $theme,

                    20,

                    false

                )

            );


        if (
            !empty(
            $slideData["visual"]
        )
        ) {

            $imageUrl =
                generateSlideImage(

                    $slideData["visual"],

                    $theme,

                    $slideData["title"]
                    ?? ""

                );


            if ($imageUrl) {

                $generatedImages[] =
                    $imageUrl;


                $requests =
                    array_merge(

                        $requests,

                        addImage(

                            $slideId,

                            $imageUrl,

                            4300000,

                            1200000,

                            2400000,

                            2600000

                        )

                    );

            }

        }

    }



    // ----------------------------
    // FALLBACK
    // ----------------------------
    else {

        $requests =
            array_merge(

                $requests,

                bulletSlide(

                    $slideId,

                    $slideData["title"]
                    ?? "",

                    $slideData["points"]
                    ?? [],

                    $theme,

                    ""

                )

            );

    }

}



// ----------------------------
// SEND TO GOOGLE
// ----------------------------

if (
    count($requests) > 0
) {

    $batch =
        new Google_Service_Slides_BatchUpdatePresentationRequest([

            "requests" =>
                $requests

        ]);


    try {

        $service
            ->presentations
            ->batchUpdate(

                $presentationId,

                $batch

            );

    } catch (
        Exception $e
    ) {

        echo json_encode([

            "error" =>
                "Google Slides error",

            "message" =>
                $e->getMessage()

        ]);

        exit;

    }

}



// ----------------------------
// SAVE PRESENTATION
// ----------------------------

$presentationUrl =
    "https://docs.google.com/presentation/d/"
    . $presentationId;


$presentationContent =
    json_encode(

        $data,

        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT

    );


$presentationName =
    $data["title"]
    ?? "AI Presentation";



try {

    $stmt =
        $GLOBALS["pdo"]->prepare(

            "
            INSERT INTO generated_items
            (
                name,
                type,
                content,
                presentation_url
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
            "

        );


    $stmt->execute([

        $presentationName,

        "presentation",

        $presentationContent,

        $presentationUrl

    ]);


    $generatedItemId =
        $GLOBALS["pdo"]->lastInsertId();


} catch (
    PDOException $e
) {

    /*
     * The Google Slides presentation
     * was already successfully created.
     *
     * Return its URL even if saving
     * the Finder item failed.
     */

    echo json_encode([

        "success" =>
            true,

        "warning" =>
            "Presentation created, but could not save it to the study file system.",

        "database_error" =>
            $e->getMessage(),

        "theme" =>
            $theme,

        "presentationId" =>
            $presentationId,

        "generatedImages" =>
            $generatedImages,

        "url" =>
            $presentationUrl

    ]);

    exit;

}



// ----------------------------
// RESPONSE
// ----------------------------

echo json_encode([

    "success" =>
        true,

    "theme" =>
        $theme,

    "presentationId" =>
        $presentationId,

    "generatedImages" =>
        $generatedImages,

    "item_id" =>
        $generatedItemId,

    "item_name" =>
        $presentationName,

    "url" =>
        $presentationUrl

]);

?>