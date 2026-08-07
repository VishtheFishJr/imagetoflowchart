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




// Refresh token if expired

if ($client->isAccessTokenExpired()) {

    echo json_encode([

        "error" => "Google token expired. Reconnect account."

    ]);

    exit;

}





$service =
    new Google_Service_Slides($client);







// ----------------------------
// READ GEMINI DATA
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




// Default theme if Gemini fails

$theme =
    $data["theme"] ?? [

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
// BUILD SLIDES
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







    // TITLE SLIDE

    if (

        $slideData["layout"] === "title"

    ) {



        $requests = array_merge(

            $requests,


            titleSlide(

                $slideId,

                $slideData["title"],

                $slideData["subtitle"] ?? "",

                $theme

            )

        );



    }






    // BULLET SLIDE
    elseif (

        $slideData["layout"] === "bullet"

    ) {



        $requests = array_merge(

            $requests,


            bulletSlide(

                $slideId,

                $slideData["title"],

                $slideData["points"],

                $theme

            )

        );


    }



}









// ----------------------------
// SEND REQUESTS
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








echo json_encode([

    "success" => true,

    "presentationId" => $presentationId,

    "url" =>

        "https://docs.google.com/presentation/d/"

        . $presentationId

]);



?>