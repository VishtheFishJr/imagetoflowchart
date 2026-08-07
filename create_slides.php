<?php


require_once 'vendor/autoload.php';

require_once 'presentation_templates.php';

session_name("PHPSESSID");

session_start();



header("Content-Type: application/json");




// Check Google connection

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



$service =
    new Google_Service_Slides($client);





$data =
    json_decode(
        file_get_contents("php://input"),
        true
    );



$slidesData =
    $data["slides"];





// Create presentation

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





$requests = [];




foreach ($slidesData as $index => $slideData) {



    $slideId =
        "slide" . $index;



    $requests[] = [

        "createSlide" => [

            "objectId" => $slideId,

            "slideLayoutReference" => [

                "predefinedLayout" => "BLANK"

            ]

        ]

    ];




    if ($slideData["layout"] == "title") {


        $requests =
            array_merge(

                $requests,

                titleSlide(

                    $slideId,

                    $slideData["title"],

                    $slideData["subtitle"] ?? ""

                )

            );


    } elseif ($slideData["layout"] == "bullet") {



        $requests =
            array_merge(

                $requests,

                bulletSlide(

                    $slideId,

                    $slideData["title"],

                    $slideData["points"]

                )

            );


    }


}







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


// hi

echo json_encode([

    "success" => true,

    "url" =>
        "https://docs.google.com/presentation/d/" . $presentationId

]);


?>