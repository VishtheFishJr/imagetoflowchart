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
        "error" => "Google account not connected"
    ]);

    exit;

}




$client = new Google_Client();


$client->setAuthConfig(
    "client_secret.json"
);


$client->setAccessToken(
    $_SESSION["google_token"]
);



// Refresh token

if ($client->isAccessTokenExpired()) {

    echo json_encode([
        "error" => "Google token expired. Reconnect account."
    ]);

    exit;

}



$service =
    new Google_Service_Slides($client);




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
        "error" => "Invalid JSON input"
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

        "name" => "Modern",

        "background" => "#FFFFFF",

        "primaryColor" => "#2563EB",

        "secondaryColor" => "#60A5FA",

        "textColor" => "#111111",

        "style" => "Modern"

    ];





// ----------------------------
// CREATE PRESENTATION
// ----------------------------


$presentation =
    new Google_Service_Slides_Presentation();



$presentation->setTitle(
    $data["title"] ?? "AI Presentation"
);



$created =
    $service->presentations->create(
        $presentation
    );



$presentationId =
    $created->presentationId;




// ----------------------------
// BUILD REQUESTS
// ----------------------------


$requests = [];




foreach ($slidesData as $index => $slideData) {



    $slideId =
        "slide_" . $index;




    // Create blank slide

    $requests[] = [

        "createSlide" => [

            "objectId" => $slideId,

            "slideLayoutReference" => [

                "predefinedLayout" => "BLANK"

            ]

        ]

    ];





    $layout =
        $slideData["layout"] ?? "bullet";





    // ----------------------------
    // TITLE
    // ----------------------------

    if ($layout === "title") {



        $requests = array_merge(

            $requests,

            titleSlide(

                $slideId,

                $slideData["title"] ?? "",

                $slideData["subtitle"] ?? "",

                $theme

            )

        );



    }





    // ----------------------------
    // BULLET
    // ----------------------------
    elseif ($layout === "bullet") {



        $requests = array_merge(

            $requests,

            bulletSlide(

                $slideId,

                $slideData["title"] ?? "",

                $slideData["points"] ?? [],

                $theme,

                $slideData["visual"] ?? ""

            )

        );


    }





    // ----------------------------
    // IMAGE + TEXT
    // ----------------------------
    elseif ($layout === "image_text") {



        $requests = array_merge(

            $requests,

            imageTextSlide(

                $slideId,

                $slideData["title"] ?? "",

                $slideData["points"] ?? [],

                $slideData["visual"] ?? "",

                $theme

            )

        );


    }





    // ----------------------------
    // COMPARISON
    // ----------------------------
    elseif ($layout === "comparison") {



        $requests = array_merge(

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
    elseif ($layout === "timeline") {



        $requests = array_merge(

            $requests,

            timelineSlide(

                $slideId,

                $slideData,

                $theme

            )

        );


    }





    // ----------------------------
    // FALLBACK
    // ----------------------------
    else {



        $requests = array_merge(

            $requests,

            bulletSlide(

                $slideId,

                $slideData["title"] ?? "",

                $slideData["points"] ?? [],

                $theme,

                $slideData["visual"] ?? ""

            )

        );


    }




}







// ----------------------------
// SEND TO GOOGLE
// ----------------------------


if (count($requests) > 0) {


    $batch =

        new Google_Service_Slides_BatchUpdatePresentationRequest([

            "requests" => $requests

        ]);



    $service
        ->presentations
        ->batchUpdate(

            $presentationId,

            $batch

        );


}






// ----------------------------
// RESPONSE
// ----------------------------


echo json_encode([

    "success" => true,

    "theme" => $theme,

    "presentationId" => $presentationId,

    "url" =>
        "https://docs.google.com/presentation/d/"
        . $presentationId

]);


?>