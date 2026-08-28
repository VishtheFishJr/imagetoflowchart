<?php

require_once 'vendor/autoload.php';
require_once 'db.php';
require_once 'presentation_templates.php';

session_name("PHPSESSID");
session_start();

header("Content-Type: application/json; charset=utf-8");

// ---------------------------------------------------------
// ALWAYS RETURN JSON FOR UNCAUGHT EXCEPTIONS / FATAL ERRORS
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

    $url =
        "https://generativelanguage.googleapis.com/v1/models/"
        . "gemini-3.1-flash-image:generateContent";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $prompt
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "responseModalities" => [
                "TEXT",
                "IMAGE"
            ]
        ]
    ];

    $ch = curl_init($url);

    if ($ch === false) {
        error_log("Could not initialize Gemini cURL.");
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-goog-api-key: " . $apiKey
        ],
        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);

    $curlError = curl_error($ch);

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    if ($response === false) {
        error_log(
            "Gemini image cURL error: " . $curlError
        );
        return null;
    }

    file_put_contents(
        __DIR__ . "/gemini_image_debug.json",
        $response
    );

    if ($httpCode !== 200) {
        error_log(
            "Gemini image generation failed: "
            . $httpCode
            . " "
            . $response
        );
        return null;
    }

    $responseData = json_decode(
        $response,
        true
    );

    if (!is_array($responseData)) {
        error_log(
            "Gemini returned invalid JSON: "
            . json_last_error_msg()
        );
        return null;
    }

    $imageData = null;

    foreach (
        $responseData["candidates"][0]["content"]["parts"] ?? []
        as $part
    ) {
        if (isset($part["text"])) {
            continue;
        }

        if (
            isset($part["inlineData"]) &&
            isset($part["inlineData"]["data"])
        ) {
            $imageData = $part["inlineData"]["data"];
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

    $imageBytes = base64_decode(
        $imageData,
        true
    );

    if ($imageBytes === false) {
        error_log(
            "Could not decode Gemini image."
        );
        return null;
    }

    $uploadDir = __DIR__ . "/uploads/";

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            error_log(
                "Could not create upload directory: "
                . $uploadDir
            );
            return null;
        }
    }

    $filename =
        "generated_slide_"
        . time()
        . "_"
        . uniqid()
        . ".png";

    $filePath =
        $uploadDir
        . $filename;

    $written = file_put_contents(
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

    $stmt =
        $GLOBALS["pdo"]->prepare(
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
                ?,
                ?
            )
            "
        );

    $stmt->execute([
        $presentationName,
        "presentation",
        $presentationContent,
        $presentationUrl,
        $_SESSION["user_id"] ?? null
    ]);

    $generatedItemId =
        $GLOBALS["pdo"]->lastInsertId();

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