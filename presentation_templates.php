<?php


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

    $id = uniqid("accent_");


    return [

        [

            "createShape" => [

                "objectId" => $id,

                "shapeType" => "RECTANGLE",


                "elementProperties" => [


                    "pageObjectId" => $slide,


                    "size" => [

                        "width" => [

                            "magnitude" => 7200000,

                            "unit" => "EMU"

                        ],


                        "height" => [

                            "magnitude" => 180000,

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










function styleText($id, $color, $size, $bold = true)
{

    return [

        "updateTextStyle" => [


            "objectId" => $id,


            "style" => [


                "foregroundColor" => [

                    "opaqueColor" => [

                        "rgbColor" => hexToRgb($color)

                    ]

                ],


                "fontSize" => [

                    "magnitude" => $size,

                    "unit" => "PT"

                ],


                "bold" => $bold


            ],



            "fields" =>
                "foregroundColor,fontSize,bold"


        ]

    ];

}









function titleSlide($slide, $title, $subtitle, $theme)
{


    $id = uniqid("title_");


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

            $theme["primaryColor"]

        )

    );







    $requests[] = [

        "createShape" => [

            "objectId" => $id,

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

            "objectId" => $id,

            "text" => $title . "\n" . $subtitle

        ]

    ];




    $requests[] = styleText(

        $id,

        $theme["textColor"],

        28,

        true

    );




    return $requests;

}









function bulletSlide($slide, $title, $points, $theme)
{


    $id = uniqid("content_");


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







    $text = $title . "\n\n";



    foreach ($points as $p) {

        $text .= "• " . $p . "\n";

    }





    $requests[] = [


        "createShape" => [


            "objectId" => $id,


            "shapeType" => "TEXT_BOX",



            "elementProperties" => [


                "pageObjectId" => $slide,


                "size" => [


                    "width" => [

                        "magnitude" => 6500000,

                        "unit" => "EMU"

                    ],


                    "height" => [

                        "magnitude" => 4500000,

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

            "objectId" => $id,

            "text" => $text

        ]

    ];






    $requests[] = styleText(

        $id,

        $theme["textColor"],

        18,

        false

    );




    return $requests;

}







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

                            "magnitude" => 1800000,

                            "unit" => "EMU"

                        ]

                    ],


                    "transform" => [

                        "translateX" => 4500000,

                        "translateY" => 1000000,

                        "scaleX" => 1,

                        "scaleY" => 1,

                        "unit" => "EMU"

                    ]

                ]

            ]

        ]

    ];

}


?>