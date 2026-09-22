<?php

require_once 'vendor/autoload.php';

require_once 'db.php';

require_once 'presentation_templates.php';

session_name("PHPSESSID");

session_start();

header("Content-Type: application/json; charset=utf-8");

// ---------------------------------------------------------
// ALWAYS RETURN JSON FOR UNCAUGHT EXCEPTIONS / FATAL ERRORS woohoo
// ---------------------------------------------------------

set_exception_handler(function ($e) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "error" => "Server error",

        "message" => $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);

    exit;

});

register_shutdown_function(function () {

    $error = error_get_last();

    if ($error !== null) {

        $fatalTypes = [

            E_ERROR,

            E_PARSE,

            E_CORE_ERROR,

            E_COMPILE_ERROR

        ];

        if (in_array($error["type"], $fatalTypes, true)) {

            if (!headers_sent()) {

                http_response_code(500);

                header("Content-Type: application/json; charset=utf-8");

            }

            echo json_encode([

                "success" => false,

                "error" => "PHP fatal error",

                "message" => $error["message"],

                "file" => $error["file"],

                "line" => $error["line"]

            ], JSON_UNESCAPED_UNICODE);

        }

    }

});

// ---------------------------------------------------------
// HELPER: JSON RESPONSE
// ---------------------------------------------------------

function jsonResponse(array $data, int $status = 200): void
{

    http_response_code($status);

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode(

        $data,

        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES

    );

    exit;

}

// ---------------------------------------------------------
// CHECK GOOGLE CONNECTION
// ---------------------------------------------------------

if (!isset($_SESSION["google_token"])) {

    jsonResponse([

        "success" => false,

        "error" => "Google account not connected"

    ], 401);

}

$client = new Google_Client();

$client->setAuthConfig("client_secret.json");

$client->setAccessToken($_SESSION["google_token"]);

if ($client->isAccessTokenExpired()) {

    jsonResponse([

        "success" => false,

        "error" => "Google token expired. Reconnect account."

    ], 401);

}

$service = new Google_Service_Slides($client);

// ---------------------------------------------------------
// GEMINI API KEY
// ---------------------------------------------------------

$apiKey = getenv("GEMINI_API_KEY");

if (!$apiKey) {

    jsonResponse([

        "success" => false,

        "error" => "GEMINI_API_KEY environment variable not set."

    ], 500);

}

// ---------------------------------------------------------
// READ AI DATA
// ---------------------------------------------------------

$rawInput = file_get_contents("php://input");

if ($rawInput === false || trim($rawInput) === "") {

    jsonResponse([

        "success" => false,

        "error" => "Empty request body"

    ], 400);

}

$data = json_decode($rawInput, true);

if (!is_array($data)) {

    jsonResponse([

        "success" => false,

        "error" => "Invalid JSON input",

        "json_error" => json_last_error_msg()

    ], 400);

}

$slidesData = $data["slides"] ?? [];

if (!is_array($slidesData)) {

    jsonResponse([

        "success" => false,

        "error" => "Invalid slides data"

    ], 400);

}

// ---------------------------------------------------------
// THEME
// ---------------------------------------------------------

$theme = $data["theme"] ?? [

    "name" => "Modern",

    "background" => "#FFFFFF",

    "primaryColor" => "#2563EB",

    "secondaryColor" => "#60A5FA",

    "textColor" => "#111111",

    "style" => "Modern"

];

if (!is_array($theme)) {

    $theme = [

        "name" => "Modern",

        "background" => "#FFFFFF",

        "primaryColor" => "#2563EB",

        "secondaryColor" => "#60A5FA",

        "textColor" => "#111111",

        "style" => "Modern"

    ];

}

// ---------------------------------------------------------
// IMAGE GENERATION
// ---------------------------------------------------------

function generateSlideImage(

    $description,

    $theme,

    $slideTitle = ""

) {

    $stabilityApiKey = getenv("STABILITY_API_KEY");

    if (!$stabilityApiKey) {

        error_log("STABILITY_API_KEY environment variable not set.");

        return null;

    }

    if (!$description) {

        return null;

    }

    // ---------------------------------------------------------
    // BUILD PROMPT
    // ---------------------------------------------------------

    $prompt =

        "A professional educational presentation graphic. "

        . "Subject: " . $slideTitle . ". "

        . "Visual description: " . $description . ". "

        . "Style: " . ($theme["style"] ?? "modern") . ". "

        . "Make the visual directly relevant to the subject. "

        . "Clean composition, professional, suitable for slides. "

        . "No text, words, letters, numbers, labels, captions, "

        . "watermarks, logos, or typography in the image.";

    // ---------------------------------------------------------
    // STABILITY API
    // ---------------------------------------------------------

    $url = "https://api.stability.ai/v2beta/stable-image/generate/sd3";

    /*
     * Stability expects multipart/form-data here.
     *
     * Do NOT manually set Content-Type. cURL will generate the
     * correct multipart boundary when CURLOPT_POSTFIELDS is an array.
     */

    $postFields = [

        "prompt" => $prompt,

        "model" => "sd3.5-medium",

        "aspect_ratio" => "16:9",

        "output_format" => "png"

    ];

    $ch = curl_init($url);

    if ($ch === false) {

        error_log("Could not initialize Stability cURL.");

        return null;

    }

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS => $postFields,

        CURLOPT_HTTPHEADER => [

            "Authorization: Bearer " . $stabilityApiKey,

            "Accept: image/*"

        ],

        CURLOPT_TIMEOUT => 120,

        CURLOPT_CONNECTTIMEOUT => 20

    ]);

    $response = curl_exec($ch);

    $curlError = curl_error($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    curl_close($ch);

    // ---------------------------------------------------------
    // CURL ERROR
    // ---------------------------------------------------------

    if ($response === false) {

        error_log(

            "Stability image cURL error: " . $curlError

        );

        return null;

    }

    // ---------------------------------------------------------
    // API ERROR
    // ---------------------------------------------------------

    if ($httpCode < 200 || $httpCode >= 300) {

        error_log(

            "Stability image generation failed. "

            . "HTTP " . $httpCode

            . " | Content-Type: " . ($contentType ?? "unknown")

            . " | Response: " . substr($response, 0, 2000)

        );

        return null;

    }

    // ---------------------------------------------------------
    // VERIFY IMAGE RESPONSE
    // ---------------------------------------------------------

    if (

        !$contentType ||

        stripos($contentType, "image/") !== 0

    ) {

        error_log(

            "Stability returned a non-image response. "

            . "Content-Type: " . ($contentType ?? "unknown")

            . " | Response: " . substr($response, 0, 2000)

        );

        return null;

    }

    if (strlen($response) === 0) {

        error_log(

            "Stability returned an empty image response."

        );

        return null;

    }

    // ---------------------------------------------------------
    // SAVE IMAGE
    // ---------------------------------------------------------

    $uploadDir = "/var/www/html/images/";

    if (!is_dir($uploadDir)) {

        if (

            !mkdir($uploadDir, 0755, true) &&

            !is_dir($uploadDir)

        ) {

            error_log(

                "Could not create upload directory: "

                . $uploadDir

            );

            $uploadDir = __DIR__ . "/uploads/";

            if (!is_dir($uploadDir)) {

                if (!mkdir($uploadDir, 0755, true)) {

                    error_log(

                        "Could not create fallback upload directory: "

                        . $uploadDir

                    );

                    return null;

                }

            }

        }

    }

    // ---------------------------------------------------------
    // UNIQUE FILENAME
    // ---------------------------------------------------------

    $filename =

        "generated_slide_"

        . time()

        . "_"

        . uniqid()

        . ".png";

    $filePath =

        rtrim($uploadDir, "/")

        . "/"

        . $filename;

    // ---------------------------------------------------------
    // WRITE IMAGE
    // ---------------------------------------------------------

    $written = file_put_contents(

        $filePath,

        $response

    );

    if ($written === false) {

        error_log(

            "Could not save generated image: "

            . $filePath

        );

        return null;

    }

    // Make sure the file actually contains data.

    if (!file_exists($filePath) || filesize($filePath) === 0) {

        error_log(

            "Generated image file is missing or empty: "

            . $filePath

        );

        return null;

    }

    // ---------------------------------------------------------
    // PUBLIC URL
    // ---------------------------------------------------------

    if (

        strpos(

            rtrim($uploadDir, "/"),

            "/var/www/html/images"

        ) !== false

    ) {

        $publicUrl =

            "https://vishthefishjr.me/images/"

            . $filename;

    } else {

        $publicUrl =

            "https://vishthefishjr.me/uploads/"

            . $filename;

    }

    return $publicUrl;

}

// ---------------------------------------------------------
// CREATE PRESENTATION
// ---------------------------------------------------------

try {

    $presentation =

        new Google_Service_Slides_Presentation();

    $presentation->setTitle(

        $data["title"] ?? "AI Presentation"

    );

    $created =

        $service

            ->presentations

            ->create(

                $presentation

            );

    $presentationId =

        $created->presentationId;

} catch (Exception $e) {

    jsonResponse([

        "success" => false,

        "error" =>

            "Could not create Google Slides presentation",

        "message" => $e->getMessage()

    ], 500);

}

// ---------------------------------------------------------
// BUILD REQUESTS
// ---------------------------------------------------------

$requests = [];

$generatedImages = [];

foreach (

    $slidesData

    as $index => $slideData

) {

    if (!is_array($slideData)) {

        continue;

    }

    $slideId =

        "slide_" . $index;

    $requests[] = [

        "createSlide" => [

            "objectId" => $slideId,

            "slideLayoutReference" => [

                "predefinedLayout" => "BLANK"

            ]

        ]

    ];

    $layout =

        $slideData["layout"]

        ?? "bullet";

    // -----------------------------------------------------
// TITLE
// -----------------------------------------------------

    if ($layout === "title") {

        $requests =

            array_merge(

                $requests,

                titleSlide(

                    $slideId,

                    $slideData["title"] ?? "",

                    $slideData["subtitle"] ?? "",

                    $theme

                )

            );

    }

    // -----------------------------------------------------
// BULLET
// -----------------------------------------------------
    elseif ($layout === "bullet") {

        $requests =

            array_merge(

                $requests,

                bulletSlide(

                    $slideId,

                    $slideData["title"] ?? "",

                    $slideData["points"] ?? [],

                    $theme,

                    ""

                )

            );

        if (!empty($slideData["visual"])) {

            $imageUrl =

                generateSlideImage(

                    $slideData["visual"],

                    $theme,

                    $slideData["title"] ?? ""

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

    // -----------------------------------------------------
// IMAGE + TEXT
// -----------------------------------------------------
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

                    $slideData["title"] ?? ""

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

                    $slideData["title"] ?? "",

                    $slideData["text"]

                    ?? $slideData["points"]

                    ?? [],

                    $visual,

                    $theme,

                    $imageUrl

                )

            );

    }

    // -----------------------------------------------------
// COMPARISON
// -----------------------------------------------------
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

    // -----------------------------------------------------
// TIMELINE
// -----------------------------------------------------
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

    // -----------------------------------------------------
// DIAGRAM
// -----------------------------------------------------
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

            as $stepIndex => $step

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

        if (!empty($slideData["visual"])) {

            $imageUrl =

                generateSlideImage(

                    $slideData["visual"],

                    $theme,

                    $slideData["title"] ?? ""

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

    // -----------------------------------------------------
// FALLBACK
// -----------------------------------------------------
    else {

        $requests =

            array_merge(

                $requests,

                bulletSlide(

                    $slideId,

                    $slideData["title"] ?? "",

                    $slideData["points"] ?? [],

                    $theme,

                    ""

                )

            );

    }

}

// ---------------------------------------------------------
// SEND TO GOOGLE
// ---------------------------------------------------------

if (count($requests) > 0) {

    $batch =

        new Google_Service_Slides_BatchUpdatePresentationRequest([

            "requests" => $requests

        ]);

    try {

        $service

            ->presentations

            ->batchUpdate(

                $presentationId,

                $batch

            );

    } catch (Exception $e) {

        jsonResponse([

            "success" => false,

            "error" => "Google Slides error",

            "message" => $e->getMessage(),

            "presentationId" => $presentationId

        ], 500);

    }

}

// ---------------------------------------------------------
// SAVE PRESENTATION
// ---------------------------------------------------------

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

// ---------------------------------------------------------
// SAVE TO DATABASE
// ---------------------------------------------------------

$generatedItemId = null;

try {

    if (

        !isset($GLOBALS["pdo"]) ||

        !($GLOBALS["pdo"] instanceof PDO)

    ) {

        throw new RuntimeException(

            "Database connection ($pdo) is not available."

        );

    }

    $userId = $_SESSION["user_id"] ?? null;

    $itemId = $data["item_id"] ?? $data["id"] ?? null;

    if ($itemId) {

        $stmt = $GLOBALS["pdo"]->prepare(

            "UPDATE generated_items

             SET presentation_url = ?

             WHERE id = ?"

        );

        $stmt->execute([$presentationUrl, $itemId]);

        $generatedItemId = $itemId;

    } else {

        if ($userId !== null) {

            $stmt = $GLOBALS["pdo"]->prepare(

                "UPDATE generated_items

                 SET presentation_url = ?

                 WHERE type = 'presentation'

                   AND user_id = ?

                   AND presentation_url IS NULL

                 ORDER BY id DESC

                 LIMIT 1"

            );

            $stmt->execute([$presentationUrl, $userId]);

        } else {

            $stmt = $GLOBALS["pdo"]->prepare(

                "UPDATE generated_items

                 SET presentation_url = ?

                 WHERE type = 'presentation'

                   AND user_id IS NULL

                   AND presentation_url IS NULL

                 ORDER BY id DESC

                 LIMIT 1"

            );

            $stmt->execute([$presentationUrl]);

        }

        if ($stmt->rowCount() > 0) {

            if ($userId !== null) {

                $getStmt = $GLOBALS["pdo"]->prepare(

                    "SELECT id FROM generated_items

                     WHERE type = 'presentation' AND user_id = ? AND presentation_url = ?

                     ORDER BY id DESC LIMIT 1"

                );

                $getStmt->execute([$userId, $presentationUrl]);

            } else {

                $getStmt = $GLOBALS["pdo"]->prepare(

                    "SELECT id FROM generated_items

                     WHERE type = 'presentation' AND user_id IS NULL AND presentation_url = ?

                     ORDER BY id DESC LIMIT 1"

                );

                $getStmt->execute([$presentationUrl]);

            }

            $generatedItemId = $getStmt->fetchColumn() ?: null;

        } else {

            $stmt = $GLOBALS["pdo"]->prepare(

                "INSERT INTO generated_items

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

                    ?,

                    ?

                )"

            );

            $stmt->execute([

                $presentationName,

                "presentation",

                $presentationContent,

                $presentationUrl,

                $userId

            ]);

            $generatedItemId = $GLOBALS["pdo"]->lastInsertId();

        }

    }

} catch (PDOException $e) {

    // The Google Slides presentation already exists.

    // Return its URL even if Finder/database storage fails.

    jsonResponse([

        "success" => true,

        "warning" =>

            "Presentation created, but could not save it to the study file system.",

        "database_error" =>

            $e->getMessage(),

        "theme" => $theme,

        "presentationId" => $presentationId,

        "generatedImages" => $generatedImages,

        "url" => $presentationUrl

    ]);

} catch (RuntimeException $e) {

    // Same behavior for a missing PDO connection.

    jsonResponse([

        "success" => true,

        "warning" =>

            "Presentation created, but could not save it to the study file system.",

        "database_error" =>

            $e->getMessage(),

        "theme" => $theme,

        "presentationId" => $presentationId,

        "generatedImages" => $generatedImages,

        "url" => $presentationUrl

    ]);

}

// ---------------------------------------------------------
// RESPONSE
// ---------------------------------------------------------

jsonResponse([

    "success" => true,

    "theme" => $theme,

    "presentationId" => $presentationId,

    "generatedImages" => $generatedImages,

    "item_id" => $generatedItemId,

    "item_name" => $presentationName,

    "url" => $presentationUrl

]);

?>