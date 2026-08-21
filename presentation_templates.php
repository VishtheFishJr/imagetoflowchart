<?php

function hexToRgb($hex)
{
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) !== 6) {
        $hex = "FFFFFF";
    }

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

                "fields" => "pageBackgroundFill.solidFill.color"
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
                        "translateX" => 0,
                        "translateY" => 0,
                        "scaleX" => 1,
                        "scaleY" => 1,
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

                "fields" => "shapeBackgroundFill.solidFill.color"
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

                "bold" => $bold,

                "fontFamily" => "Aptos"
            ],

            "fields" =>
                "foregroundColor,fontSize,bold,fontFamily"
        ]
    ];
}


function createTextBox(
    $slide,
    $text,
    $x,
    $y,
    $width,
    $height,
    $theme,
    $size = 18,
    $bold = false
) {

    $id = uniqid("text_");

    return [
        [
            "createShape" => [
                "objectId" => $id,

                "shapeType" => "TEXT_BOX",

                "elementProperties" => [
                    "pageObjectId" => $slide,

                    "size" => [
                        "width" => [
                            "magnitude" => $width,
                            "unit" => "EMU"
                        ],

                        "height" => [
                            "magnitude" => $height,
                            "unit" => "EMU"
                        ]
                    ],

                    "transform" => [
                        "translateX" => $x,
                        "translateY" => $y,
                        "scaleX" => 1,
                        "scaleY" => 1,
                        "unit" => "EMU"
                    ]
                ]
            ]
        ],

        [
            "insertText" => [
                "objectId" => $id,
                "text" => $text
            ]
        ],

        styleText(
            $id,
            $theme["textColor"],
            $size,
            $bold
        )
    ];
}


function titleSlide($slide, $title, $subtitle, $theme)
{
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

    $requests = array_merge(
        $requests,

        createTextBox(
            $slide,
            $title . "\n\n" . $subtitle,
            700000,
            1500000,
            6500000,
            2000000,
            $theme,
            30,
            true
        )
    );

    return $requests;
}


function bulletSlide(
    $slide,
    $title,
    $points,
    $theme,
    $visual = ""
) {

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

    /*
     * Only display the visual description if
     * an actual generated image was not supplied.
     */
    if ($visual) {
        $text .= "\n" . $visual;
    }

    $requests = array_merge(
        $requests,

        createTextBox(
            $slide,
            $text,
            700000,
            900000,
            6500000,
            4500000,
            $theme,
            18,
            false
        )
    );

    return $requests;
}


function imageTextSlide(
    $slide,
    $title,
    $points,
    $visual,
    $theme,
    $imageUrl = null
) {

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


    // -------------------------
    // LEFT TEXT
    // -------------------------

    $text = $title . "\n\n";

    foreach ($points as $p) {
        $text .= "• " . $p . "\n";
    }

    $requests = array_merge(
        $requests,

        createTextBox(
            $slide,
            $text,
            500000,
            900000,
            3500000,
            4000000,
            $theme,
            18,
            false
        )
    );


    // -------------------------
    // GENERATED IMAGE
    // -------------------------

    if ($imageUrl) {

        $requests = array_merge(
            $requests,

            addImage(
                $slide,
                $imageUrl,
                4200000,
                1200000,
                2500000,
                3000000
            )
        );

    } else {

        // Fallback if image generation failed

        $requests = array_merge(
            $requests,

            createTextBox(
                $slide,
                "Visual:\n" . $visual,
                4200000,
                1300000,
                2500000,
                2500000,
                $theme,
                14,
                false
            )
        );

    }


    return $requests;
}


function comparisonSlide($slide, $data, $theme)
{
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

    $leftTitle =
        $data["leftTitle"]
        ?? "Left";

    $rightTitle =
        $data["rightTitle"]
        ?? "Right";

    $leftPoints =
        $data["leftPoints"]
        ?? $data["left"]
        ?? [];

    $rightPoints =
        $data["rightPoints"]
        ?? $data["right"]
        ?? [];


    $text =
        ($data["title"] ?? "Comparison")
        . "\n\n"

        . $leftTitle
        . "\n";

    foreach ($leftPoints as $point) {
        $text .= "• " . $point . "\n";
    }

    $text .= "\n";

    $text .=
        $rightTitle
        . "\n";

    foreach ($rightPoints as $point) {
        $text .= "• " . $point . "\n";
    }


    $requests = array_merge(
        $requests,

        createTextBox(
            $slide,
            $text,
            600000,
            900000,
            6500000,
            4000000,
            $theme,
            18,
            false
        )
    );

    return $requests;
}


function timelineSlide($slide, $data, $theme)
{
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

    $text =
        ($data["title"] ?? "Timeline")
        . "\n\n";

    $points =
        $data["points"]
        ?? $data["steps"]
        ?? [];

    foreach ($points as $point) {
        $text .= "→ " . $point . "\n";
    }

    $requests = array_merge(
        $requests,

        createTextBox(
            $slide,
            $text,
            700000,
            900000,
            6500000,
            4000000,
            $theme,
            18,
            false
        )
    );

    return $requests;
}


/*
 * Add an image to a slide.
 *
 * Google Slides requires the URL to be publicly accessible.
 */
function addImage(
    $slide,
    $url,
    $x = 4500000,
    $y = 1000000,
    $width = 2500000,
    $height = 1800000
) {

    $id = uniqid("image_");

    return [
        [
            "createImage" => [
                "objectId" => $id,

                "url" => $url,

                "elementProperties" => [
                    "pageObjectId" => $slide,

                    "size" => [
                        "width" => [
                            "magnitude" => $width,
                            "unit" => "EMU"
                        ],

                        "height" => [
                            "magnitude" => $height,
                            "unit" => "EMU"
                        ]
                    ],

                    "transform" => [
                        "translateX" => $x,
                        "translateY" => $y,
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