<?php

// ================================================================
// HELPERS
// ================================================================

function hexToRgb($hex)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [
        'red'   => hexdec(substr($hex, 0, 2)) / 255,
        'green' => hexdec(substr($hex, 2, 2)) / 255,
        'blue'  => hexdec(substr($hex, 4, 2)) / 255,
    ];
}

function rgbColor($hex)
{
    return ['rgbColor' => hexToRgb($hex)];
}

function emu($pt)
{
    // 1 pt = 12700 EMU
    return (int) ($pt * 12700);
}

// ================================================================
// LOW-LEVEL PRIMITIVES
// ================================================================

/**
 * Set a slide's solid background colour.
 */
function setBackground($slideId, $color)
{
    return [[
        'updatePageProperties' => [
            'objectId'       => $slideId,
            'pageProperties' => [
                'pageBackgroundFill' => [
                    'solidFill' => ['color' => rgbColor($color)],
                ],
            ],
            'fields' => 'pageBackgroundFill.solidFill.color',
        ],
    ]];
}

/**
 * Create a coloured rectangle shape.
 */
function createRect($slideId, $x, $y, $w, $h, $color, $alpha = 1.0)
{
    $id = uniqid('rect_');
    return [
        [
            'createShape' => [
                'objectId'          => $id,
                'shapeType'         => 'RECTANGLE',
                'elementProperties' => [
                    'pageObjectId' => $slideId,
                    'size'         => [
                        'width'  => ['magnitude' => $w, 'unit' => 'EMU'],
                        'height' => ['magnitude' => $h, 'unit' => 'EMU'],
                    ],
                    'transform' => [
                        'translateX' => $x, 'translateY' => $y,
                        'scaleX' => 1, 'scaleY' => 1, 'unit' => 'EMU',
                    ],
                ],
            ],
        ],
        [
            'updateShapeProperties' => [
                'objectId'         => $id,
                'shapeProperties'  => [
                    'shapeBackgroundFill' => [
                        'solidFill' => [
                            'color' => rgbColor($color),
                            'alpha' => $alpha,
                        ],
                    ],
                    'outline' => ['outlineFill' => ['solidFill' => ['color' => rgbColor($color)]]],
                ],
                'fields' => 'shapeBackgroundFill.solidFill.color,shapeBackgroundFill.solidFill.alpha',
            ],
        ],
    ];
}

/**
 * Create a text box and style all its text.
 *
 * $runs = [
 *   ['text' => '...', 'size' => 18, 'bold' => false, 'color' => '#111'],
 *   ...
 * ]
 *
 * For simple single-run use, pass a plain string to $text and use $size/$bold/$color.
 */
function createTextBox(
    $slideId,
    $text,
    $x, $y, $w, $h,
    $theme,
    $size  = 16,
    $bold  = false,
    $color = null
) {
    $id    = uniqid('tb_');
    $color = $color ?? $theme['textColor'];

    return [
        [
            'createShape' => [
                'objectId'          => $id,
                'shapeType'         => 'TEXT_BOX',
                'elementProperties' => [
                    'pageObjectId' => $slideId,
                    'size'         => [
                        'width'  => ['magnitude' => $w, 'unit' => 'EMU'],
                        'height' => ['magnitude' => $h, 'unit' => 'EMU'],
                    ],
                    'transform' => [
                        'translateX' => $x, 'translateY' => $y,
                        'scaleX' => 1, 'scaleY' => 1, 'unit' => 'EMU',
                    ],
                ],
            ],
        ],
        [
            'insertText' => ['objectId' => $id, 'text' => $text],
        ],
        [
            'updateTextStyle' => [
                'objectId' => $id,
                'style'    => [
                    'foregroundColor' => ['opaqueColor' => rgbColor($color)],
                    'fontSize'        => ['magnitude' => $size, 'unit' => 'PT'],
                    'bold'            => $bold,
                    'fontFamily'      => 'Aptos',
                ],
                'fields' => 'foregroundColor,fontSize,bold,fontFamily',
            ],
        ],
    ];
}

/**
 * Create a text box that has TWO styled runs:
 *   run 1 = title line (bigger/bold)
 *   run 2 = body text
 */
function createSplitTextBox(
    $slideId,
    $titleText, $titleSize, $titleColor,
    $bodyText,  $bodySize,  $bodyColor,
    $x, $y, $w, $h
) {
    $id  = uniqid('stb_');
    $raw = $titleText . "\n" . $bodyText;
    $titleLen = mb_strlen($titleText) + 1; // +1 for newline
    $bodyLen  = mb_strlen($bodyText);

    return [
        [
            'createShape' => [
                'objectId'          => $id,
                'shapeType'         => 'TEXT_BOX',
                'elementProperties' => [
                    'pageObjectId' => $slideId,
                    'size'         => [
                        'width'  => ['magnitude' => $w, 'unit' => 'EMU'],
                        'height' => ['magnitude' => $h, 'unit' => 'EMU'],
                    ],
                    'transform' => [
                        'translateX' => $x, 'translateY' => $y,
                        'scaleX' => 1, 'scaleY' => 1, 'unit' => 'EMU',
                    ],
                ],
            ],
        ],
        [
            'insertText' => ['objectId' => $id, 'text' => $raw],
        ],
        // Style title run
        [
            'updateTextStyle' => [
                'objectId'    => $id,
                'textRange'   => ['type' => 'FIXED_RANGE', 'startIndex' => 0, 'endIndex' => mb_strlen($titleText)],
                'style'       => [
                    'foregroundColor' => ['opaqueColor' => rgbColor($titleColor)],
                    'fontSize'        => ['magnitude' => $titleSize, 'unit' => 'PT'],
                    'bold'            => true,
                    'fontFamily'      => 'Aptos',
                ],
                'fields' => 'foregroundColor,fontSize,bold,fontFamily',
            ],
        ],
        // Style body run
        [
            'updateTextStyle' => [
                'objectId'    => $id,
                'textRange'   => ['type' => 'FIXED_RANGE', 'startIndex' => $titleLen, 'endIndex' => $titleLen + $bodyLen],
                'style'       => [
                    'foregroundColor' => ['opaqueColor' => rgbColor($bodyColor)],
                    'fontSize'        => ['magnitude' => $bodySize, 'unit' => 'PT'],
                    'bold'            => false,
                    'fontFamily'      => 'Aptos',
                ],
                'fields' => 'foregroundColor,fontSize,bold,fontFamily',
            ],
        ],
    ];
}

/**
 * Add an image from a URL.
 */
function addImage($slideId, $url, $x, $y, $w, $h)
{
    return [[
        'createImage' => [
            'url'               => $url,
            'elementProperties' => [
                'pageObjectId' => $slideId,
                'size'         => [
                    'width'  => ['magnitude' => $w, 'unit' => 'EMU'],
                    'height' => ['magnitude' => $h, 'unit' => 'EMU'],
                ],
                'transform' => [
                    'translateX' => $x, 'translateY' => $y,
                    'scaleX' => 1, 'scaleY' => 1, 'unit' => 'EMU',
                ],
            ],
        ],
    ]];
}

// ================================================================
// DECORATIVE HELPERS
// ================================================================

/**
 * Full-width accent bar at a given Y position.
 */
function accentBar($slideId, $color, $y = 0, $h = 200000)
{
    return createRect($slideId, 0, $y, 9144000, $h, $color);
}

/**
 * Left vertical accent stripe.
 */
function leftStripe($slideId, $color, $w = 220000)
{
    return createRect($slideId, 0, 0, $w, 5143000, $color);
}

// ================================================================
// SLIDE LAYOUT FUNCTIONS
// ================================================================

// ----------------------------------------------------------------
// 1. TITLE SLIDE
// ----------------------------------------------------------------
function titleSlide($slideId, $title, $subtitle, $theme, $titleFontSize = 40, $subtitleFontSize = 22)
{
    $bg      = $theme['background']   ?? '#1a1a2e';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#ffffff';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));

    // Bottom accent bar
    $reqs = array_merge($reqs, createRect($slideId, 0, 4700000, 9144000, 443000, $primary));

    // Thin top bar
    $reqs = array_merge($reqs, createRect($slideId, 0, 0, 9144000, 120000, $accent));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        700000, 1600000, 7700000, 1600000,
        $theme, (int)$titleFontSize, true, $textCol
    ));

    // Subtitle
    if ($subtitle) {
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $subtitle,
            700000, 3300000, 7700000, 1200000,
            $theme, (int)$subtitleFontSize, false, $accent
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 2. SECTION BREAK
// ----------------------------------------------------------------
function sectionBreakSlide($slideId, $title, $subtitle, $theme, $titleFontSize = 42, $subtitleFontSize = 20)
{
    $primary  = $theme['primaryColor']   ?? '#4f46e5';
    $accent   = $theme['accentColor']    ?? $theme['secondaryColor'] ?? '#a5b4fc';
    $textCol  = $theme['textColor']      ?? '#ffffff';
    $bg       = $theme['background']     ?? '#0f172a';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $primary));

    // Overlay darker band
    $reqs = array_merge($reqs, createRect($slideId, 0, 0, 9144000, 5143000, $bg, 0.35));

    // Horizontal rule
    $reqs = array_merge($reqs, createRect($slideId, 500000, 2650000, 8144000, 80000, $accent));

    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        700000, 1500000, 7700000, 1600000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    if ($subtitle) {
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $subtitle,
            700000, 3000000, 7700000, 900000,
            $theme, (int)$subtitleFontSize, false, $accent
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 3. AGENDA
// ----------------------------------------------------------------
function agendaSlide($slideId, $title, $items, $theme, $titleFontSize = 32, $bodyFontSize = 18)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        500000, 300000, 8100000, 900000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    // Items – numbered list with coloured circle markers
    $colWidth = 4000000;
    $startX   = 500000;
    $startY   = 1400000;
    $itemH    = 500000;

    foreach ($items as $i => $item) {
        $numStr = ($i + 1) . '.  ' . $item;
        $yPos   = $startY + $i * ($itemH + 100000);

        // Coloured dot
        $reqs = array_merge($reqs, createRect($slideId, $startX, $yPos + 50000, 120000, 120000, $accent));

        $reqs = array_merge($reqs, createTextBox(
            $slideId, '  ' . ($i + 1) . '.  ' . $item,
            $startX, $yPos, 8100000, $itemH,
            $theme, (int)$bodyFontSize, false, $textCol
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 4. HIERARCHICAL BULLET (main + subtopics)
// ----------------------------------------------------------------
function hierarchicalBulletSlide($slideId, $title, $points, $theme, $titleFontSize = 28, $bodyFontSize = 16, $imageUrl = null)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        500000, 280000, 8100000, 800000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    // Content area – if image, squeeze text to left half
    $textW = $imageUrl ? 4100000 : 8400000;

    // Build the bullet text
    $bulletText = '';
    foreach ($points as $point) {
        if (is_array($point)) {
            $bulletText .= '• ' . ($point['text'] ?? '') . "\n";
            foreach (($point['subtopics'] ?? []) as $sub) {
                $bulletText .= '    ◦ ' . $sub . "\n";
            }
        } else {
            $bulletText .= '• ' . $point . "\n";
        }
    }
    $bulletText = rtrim($bulletText);

    $reqs = array_merge($reqs, createTextBox(
        $slideId, $bulletText,
        500000, 1200000, $textW, 3700000,
        $theme, (int)$bodyFontSize, false, $textCol
    ));

    // Right-side image
    if ($imageUrl) {
        $reqs = array_merge($reqs, addImage($slideId, $imageUrl, 4750000, 1200000, 4100000, 3500000));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 5. IMAGE + TEXT
// ----------------------------------------------------------------
function imageTextSlide($slideId, $title, $points, $visual, $theme, $imageUrl = null, $titleFontSize = 28, $bodyFontSize = 16)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        500000, 280000, 8100000, 800000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    $textLines = is_array($points) ? $points : [$points];
    $bodyText  = implode("\n\n", array_map(fn($p) => '• ' . $p, $textLines));

    if ($imageUrl) {
        // Image on right, text on left
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $bodyText,
            400000, 1200000, 4200000, 3700000,
            $theme, (int)$bodyFontSize, false, $textCol
        ));
        $reqs = array_merge($reqs, addImage($slideId, $imageUrl, 4750000, 1100000, 4100000, 3700000));
    } else {
        // Fallback: full width text + visual description
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $bodyText . ($visual ? "\n\n[Visual: " . $visual . "]" : ''),
            400000, 1200000, 8400000, 3700000,
            $theme, (int)$bodyFontSize, false, $textCol
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 6. COMPARISON (two columns)
// ----------------------------------------------------------------
function comparisonSlide($slideId, $data, $theme, $titleFontSize = 28, $bodyFontSize = 15)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $slideTitle = $data['title'] ?? 'Comparison';
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $slideTitle,
        500000, 280000, 8100000, 800000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    // Centre divider
    $reqs = array_merge($reqs, createRect($slideId, 4520000, 1200000, 80000, 3700000, $accent));

    // Left column
    $leftTitle  = $data['leftTitle']  ?? 'A';
    $leftPoints = $data['leftPoints'] ?? [];
    $leftText   = $leftTitle . "\n\n" . implode("\n", array_map(fn($p) => '• ' . $p, $leftPoints));
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $leftText,
        400000, 1250000, 3950000, 3600000,
        $theme, (int)$bodyFontSize, false, $textCol
    ));

    // Right column
    $rightTitle  = $data['rightTitle']  ?? 'B';
    $rightPoints = $data['rightPoints'] ?? [];
    $rightText   = $rightTitle . "\n\n" . implode("\n", array_map(fn($p) => '• ' . $p, $rightPoints));
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $rightText,
        4750000, 1250000, 3950000, 3600000,
        $theme, (int)$bodyFontSize, false, $textCol
    ));

    return $reqs;
}

// ----------------------------------------------------------------
// 7. STATS (key numbers)
// ----------------------------------------------------------------
function statsSlide($slideId, $title, $stats, $theme, $titleFontSize = 30, $bodyFontSize = 18)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        500000, 280000, 8100000, 800000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    $count   = count($stats);
    $colW    = (int) (8200000 / max($count, 1));
    $startX  = 500000;

    foreach ($stats as $i => $stat) {
        $x = $startX + $i * $colW;

        // Coloured background card
        $reqs = array_merge($reqs, createRect($slideId, $x + 80000, 1400000, $colW - 160000, 2600000, $primary, 0.12));

        // Big number/value
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $stat['value'] ?? '—',
            $x + 100000, 1600000, $colW - 200000, 1200000,
            $theme, min(52, (int)($bodyFontSize * 2.5)), true, $primary
        ));

        // Label
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $stat['label'] ?? '',
            $x + 100000, 2900000, $colW - 200000, 900000,
            $theme, (int)$bodyFontSize, false, $textCol
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 8. QUOTE / KEY DEFINITION
// ----------------------------------------------------------------
function quoteSlide($slideId, $title, $quote, $attribution, $theme, $titleFontSize = 26, $bodyFontSize = 20)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));

    // Left thick accent stripe
    $reqs = array_merge($reqs, createRect($slideId, 0, 0, 280000, 5143000, $primary));

    // Subtle tinted background
    $reqs = array_merge($reqs, createRect($slideId, 280000, 0, 8864000, 5143000, $primary, 0.07));

    // Title / label
    if ($title) {
        $reqs = array_merge($reqs, createTextBox(
            $slideId, strtoupper($title),
            700000, 500000, 8000000, 700000,
            $theme, (int)$titleFontSize, true, $primary
        ));
    }

    // Quotation mark decoration
    $reqs = array_merge($reqs, createTextBox(
        $slideId, "\u{201C}",
        600000, 900000, 1200000, 1200000,
        $theme, 100, true, $accent
    ));

    // Quote body
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $quote,
        900000, 1400000, 7500000, 2400000,
        $theme, (int)$bodyFontSize, false, $textCol
    ));

    // Attribution
    if ($attribution) {
        $reqs = array_merge($reqs, createTextBox(
            $slideId, '— ' . $attribution,
            900000, 3900000, 7500000, 600000,
            $theme, max(12, (int)$bodyFontSize - 4), false, $accent
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 9. THREE-COLUMN
// ----------------------------------------------------------------
function threeColumnSlide($slideId, $title, $columns, $theme, $titleFontSize = 28, $bodyFontSize = 15)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        500000, 280000, 8100000, 800000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    $colW   = 2700000;
    $gap    = 200000;
    $startX = 350000;

    foreach (array_slice($columns, 0, 3) as $i => $col) {
        $x = $startX + $i * ($colW + $gap);

        // Card background
        $reqs = array_merge($reqs, createRect($slideId, $x, 1250000, $colW, 3600000, $primary, 0.10));

        // Column heading bar
        $reqs = array_merge($reqs, createRect($slideId, $x, 1250000, $colW, 380000, $accent));

        // Heading text
        $heading = $col['heading'] ?? 'Concept ' . ($i + 1);
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $heading,
            $x + 80000, 1280000, $colW - 160000, 320000,
            $theme, (int)($bodyFontSize + 2), true, '#ffffff'
        ));

        // Points
        $pts = is_array($col['points'] ?? null) ? $col['points'] : [];
        $body = implode("\n\n", array_map(fn($p) => '• ' . $p, $pts));
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $body,
            $x + 80000, 1700000, $colW - 160000, 3000000,
            $theme, (int)$bodyFontSize, false, $textCol
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 10. DIAGRAM / PROCESS STEPS
// ----------------------------------------------------------------
function diagramSlide($slideId, $title, $steps, $theme, $titleFontSize = 28, $bodyFontSize = 16, $imageUrl = null)
{
    $bg      = $theme['background']   ?? '#ffffff';
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $textCol = $theme['textColor']    ?? '#111111';
    $accent  = $theme['accentColor']  ?? $theme['secondaryColor'] ?? '#a5b4fc';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));
    $reqs = array_merge($reqs, accentBar($slideId, $primary, 0, 160000));

    // Title
    $reqs = array_merge($reqs, createTextBox(
        $slideId, $title,
        500000, 280000, 8100000, 800000,
        $theme, (int)$titleFontSize, true, '#ffffff'
    ));

    $count   = count($steps);
    $stepW   = $imageUrl ? 4000000 : 8200000;
    $stepH   = max(350000, (int)(3500000 / max($count, 1)));
    $startY  = 1300000;
    $x       = 400000;

    foreach ($steps as $i => $step) {
        $y = $startY + $i * ($stepH + 80000);

        // Step number bubble
        $reqs = array_merge($reqs, createRect($slideId, $x, $y + 40000, 280000, 280000, $accent));
        $reqs = array_merge($reqs, createTextBox(
            $slideId, (string)($i + 1),
            $x + 30000, $y + 30000, 220000, 300000,
            $theme, (int)($bodyFontSize + 2), true, '#ffffff'
        ));

        // Arrow connector (except last)
        if ($i < $count - 1) {
            $reqs = array_merge($reqs, createRect(
                $slideId, $x + 120000, $y + $stepH + 10000, 40000, 70000, $accent, 0.6
            ));
        }

        // Step text
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $step,
            $x + 360000, $y, $stepW - 380000, $stepH,
            $theme, (int)$bodyFontSize, false, $textCol
        ));
    }

    // Right image
    if ($imageUrl) {
        $reqs = array_merge($reqs, addImage($slideId, $imageUrl, 4650000, 1250000, 4200000, 3600000));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 11. IMAGE FULL (full-slide image with title overlay)
// ----------------------------------------------------------------
function imageFullSlide($slideId, $title, $theme, $imageUrl = null, $titleFontSize = 26)
{
    $primary = $theme['primaryColor'] ?? '#4f46e5';
    $bg      = $theme['background']   ?? '#111111';

    $reqs = [];
    $reqs = array_merge($reqs, setBackground($slideId, $bg));

    if ($imageUrl) {
        $reqs = array_merge($reqs, addImage($slideId, $imageUrl, 0, 0, 9144000, 5143000));
    }

    // Dark overlay for text legibility
    $reqs = array_merge($reqs, createRect($slideId, 0, 3800000, 9144000, 1343000, '#000000', 0.55));

    if ($title) {
        $reqs = array_merge($reqs, createTextBox(
            $slideId, $title,
            400000, 3900000, 8300000, 1000000,
            $theme, (int)$titleFontSize, true, '#ffffff'
        ));
    }

    return $reqs;
}

// ----------------------------------------------------------------
// 12. CLASSIC BULLET (legacy compat)
// ----------------------------------------------------------------
function bulletSlide($slideId, $title, $points, $theme, $visual = '', $titleFontSize = 28, $bodyFontSize = 16)
{
    return hierarchicalBulletSlide($slideId, $title, $points, $theme, $titleFontSize, $bodyFontSize);
}

// ----------------------------------------------------------------
// 13. TIMELINE (legacy compat)
// ----------------------------------------------------------------
function timelineSlide($slideId, $data, $theme)
{
    $points = $data['points'] ?? [];
    $tfSize = (int)($data['titleFontSize'] ?? 28);
    $bfSize = (int)($data['bodyFontSize']  ?? 16);

    $steps = array_map(fn($p) => '→ ' . $p, $points);
    return diagramSlide($slideId, $data['title'] ?? 'Timeline', $steps, $theme, $tfSize, $bfSize);
}

?>