<?php


function titleSlide($slide, $title, $subtitle)
{

    return [

        [
            "createShape" => [
                "objectId" => "title_box",
                "shapeType" => "TEXT_BOX",
                "elementProperties" => [
                    "pageObjectId" => $slide,
                    "size" => [
                        "width" => [
                            "magnitude" => 6000000,
                            "unit" => "EMU"
                        ],
                        "height" => [
                            "magnitude" => 1000000,
                            "unit" => "EMU"
                        ]
                    ],
                    "transform" => [
                        "scaleX" => 1,
                        "scaleY" => 1,
                        "translateX" => 800000,
                        "translateY" => 1500000,
                        "unit" => "EMU"
                    ]
                ]
            ]
        ],

        [
            "insertText" => [
                "objectId" => "title_box",
                "text" => $title
            ]
        ]

    ];

}




function bulletSlide($slide, $title, $points)
{

    $text =
        $title . "\n\n";


    foreach ($points as $point) {

        $text .= "• " . $point . "\n";

    }



    return [

        [
            "createShape" => [
                "objectId" => uniqid("textbox_"),
                "shapeType" => "TEXT_BOX",

                "elementProperties" => [

                    "pageObjectId" => $slide,

                    "size" => [
                        "width" => [
                            "magnitude" => 6000000,
                            "unit" => "EMU"
                        ],
                        "height" => [
                            "magnitude" => 4000000,
                            "unit" => "EMU"
                        ]
                    ],

                    "transform" => [
                        "scaleX" => 1,
                        "scaleY" => 1,
                        "translateX" => 700000,
                        "translateY" => 800000,
                        "unit" => "EMU"
                    ]

                ]

            ]

        ]

    ];

}

?>