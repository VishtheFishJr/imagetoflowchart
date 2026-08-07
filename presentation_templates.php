<?php


// Convert HEX color to Google Slides RGB format
function hexToRgb($hex)
{

    $hex = str_replace("#", "", $hex);


    return [

        "red" => hexdec(substr($hex, 0, 2)) / 255,

        "green" => hexdec(substr($hex, 2, 2)) / 255,

        "blue" => hexdec(substr($hex, 4, 2)) / 255

    ];

}





function setBackground($slide, $color)
{

    return [

        [
            "updatePageProperties" => [

                "objectId" => $slide,

                "pageProperties" => [

                    "pageBackgroundFill" => [

                        "solidFill" => [

                            "color" => [

                                "rgbColor" => hexToRgb($color)

                            ]

                        ]

                    ]

                ],

                "fields" =>
                    "pageBackgroundFill.solidFill.color"

            ]

        ]

    ];

}





function addAccentBar($slide, $color)
{


    $id = uniqid("bar_");


    return [

        [

            "createShape" => [

                "objectId" => $id,

                "shapeType" => "RECTANGLE",

                "elementProperties" => [

                    "pageObjectId" => $slide,

                    "size" => [

                        "width" => [

                            "magnitude" => 7000000,

                            "unit" => "EMU"

                        ],

                        "height" => [

                            "magnitude" => 150000,

                            "unit" => "EMU"

                        ]

                    ],


                    "transform" => [

                        "scaleX" => 1,

                        "scaleY" => 1,

                        "translateX" => 0,

                        "translateY" => 0,

                        "unit" => "EMU"

                    ]

                ]

            ]

        ],


        [

            "updateShapeProperties" => [

                "objectId" => $id,

                "shapeProperties" => [

                    "shapeBackgroundFill" => [

                        "solidFill" => [

                            "color" => [

                                "rgbColor" => hexToRgb($color)

                            ]

                        ]

                    ]

                ],

                "fields" =>
                    "shapeBackgroundFill.solidFill.color"

            ]

        ]

    ];

}








function titleSlide($slide, $title, $subtitle, $theme)
{


    $titleId = uniqid("title_");


    $requests = [];


    // Background

    $requests = array_merge(

        $requests,

        setBackground(

            $slide,

            $theme["background"]

        )

    );



    // Accent

    $requests = array_merge(

        $requests,

        addAccentBar(

            $slide,

            $theme["primaryColor"]

        )

    );





    // Text box

    $requests[] = [

        "createShape" => [

            "objectId" => $titleId,

            "shapeType" => "TEXT_BOX",

            "elementProperties" => [

                "pageObjectId" => $slide,

                "size" => [

                    "width" => [

                        "magnitude" => 6500000,

                        "unit" => "EMU"

                    ],

                    "height" => [

                        "magnitude" => 1500000,

                        "unit" => "EMU"

                    ]

                ],


                "transform" => [

                    "translateX" => 700000,

                    "translateY" => 1500000,

                    "scaleX" => 1,

                    "scaleY" => 1,

                    "unit" => "EMU"

                ]

            ]

        ]

    ];





    $requests[] = [

        "insertText" => [

            "objectId" => $titleId,

            "text" =>
                $title .
                "\n\n" .
                $subtitle

        ]

    ];





    return $requests;


}









function bulletSlide($slide, $title, $points, $theme)
{


    $textboxId = uniqid("content_");


    $requests = [];



    $requests = array_merge(

        $requests,

        setBackground(

            $slide,

            $theme["background"]

        )

    );




    $requests = array_merge(

        $requests,

        addAccentBar(

            $slide,

            $theme["secondaryColor"]

        )

    );





    $text =
        $title .
        "\n\n";



    foreach ($points as $point) {

        $text .=
            "• " .
            $point .
            "\n";

    }





    $requests[] = [

        "createShape" => [

            "objectId" => $textboxId,

            "shapeType" => "TEXT_BOX",


            "elementProperties" => [

                "pageObjectId" => $slide,


                "size" => [

                    "width" => [

                        "magnitude" => 6500000,

                        "unit" => "EMU"

                    ],


                    "height" => [

                        "magnitude" => 4000000,

                        "unit" => "EMU"

                    ]

                ],



                "transform" => [

                    "translateX" => 700000,

                    "translateY" => 900000,

                    "scaleX" => 1,

                    "scaleY" => 1,

                    "unit" => "EMU"

                ]

            ]

        ]

    ];






    $requests[] = [

        "insertText" => [

            "objectId" => $textboxId,

            "text" => $text

        ]

    ];





    return $requests;


}






// Future image support
function addImage($slide, $url)
{

    return [

        [

            "createImage" => [

                "url" => $url,

                "elementProperties" => [

                    "pageObjectId" => $slide,

                    "size" => [

                        "width" => [

                            "magnitude" => 2500000,

                            "unit" => "EMU"

                        ],

                        "height" => [

                            "magnitude" => 2000000,

                            "unit" => "EMU"

                        ]

                    ]

                ]

            ]

        ]

    ];

}



?>