<?php

require_once 'vendor/autoload.php';

session_name("PHPSESSID");
session_start();

header('Content-Type: application/json');


// ------------------------------------------------------------
// Make sure the user has connected Google
// ------------------------------------------------------------

if (!isset($_SESSION['google_token'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'Google account is not connected.'
    ]);

    exit;
}


// ------------------------------------------------------------
// Get form title / description
// ------------------------------------------------------------

$formTitle = trim($_POST['title'] ?? '');

if ($formTitle === '') {
    $formTitle = 'AI Generated Form';
}

$formDescription = trim($_POST['description'] ?? '');


// ------------------------------------------------------------
// Get questions from the request
// ------------------------------------------------------------

$questionsJson = $_POST['questions'] ?? '';

if (empty($questionsJson)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'No questions were provided.'
    ]);

    exit;
}


$questions = json_decode($questionsJson, true);


if (!is_array($questions)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Invalid questions JSON.'
    ]);

    exit;
}


if (count($questions) === 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'No questions found.'
    ]);

    exit;
}


// ------------------------------------------------------------
// Set up Google Client
// ------------------------------------------------------------

$client = new Google_Client();

$client->setAuthConfig('client_secret.json');

$client->setRedirectUri(
    'https://vishthefishjr.me/oauth_callback.php'
);

$client->setAccessType('offline');

$client->setAccessToken($_SESSION['google_token']);


// ------------------------------------------------------------
// Refresh expired token if possible
// ------------------------------------------------------------

if ($client->isAccessTokenExpired()) {

    $refreshToken = $client->getRefreshToken();

    if ($refreshToken) {

        $newToken = $client->fetchAccessTokenWithRefreshToken(
            $refreshToken
        );

        if (isset($newToken['error'])) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Google authorization expired. Please reconnect your Google account.'
            ]);

            exit;
        }

        $_SESSION['google_token'] = $client->getAccessToken();

    } else {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' => 'Google authorization expired. Please reconnect your Google account.'
        ]);

        exit;
    }
}


// ------------------------------------------------------------
// Get access token
// ------------------------------------------------------------

$token = $client->getAccessToken();

if (!isset($token['access_token'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'No Google access token available.'
    ]);

    exit;
}

$accessToken = $token['access_token'];


// ------------------------------------------------------------
// Create the Google Form
// ------------------------------------------------------------

$formData = [
    'info' => [
        'title' => $formTitle,
        'documentTitle' => $formTitle
    ]
];


$ch = curl_init(
    'https://forms.googleapis.com/v1/forms'
);

curl_setopt_array($ch, [

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ],

    CURLOPT_POSTFIELDS => json_encode($formData)

]);


$response = curl_exec($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$curlError = curl_error($ch);

curl_close($ch);


if ($response === false) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Could not connect to Google.',
        'details' => $curlError
    ]);

    exit;
}


$form = json_decode(
    $response,
    true
);


if ($httpCode < 200 || $httpCode >= 300) {

    http_response_code($httpCode);

    echo json_encode([
        'success' => false,
        'error' => 'Google Forms API returned an error.',
        'details' => $form
    ]);

    exit;
}


if (!isset($form['formId'])) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Google did not return a form ID.',
        'details' => $form
    ]);

    exit;
}


$formId = $form['formId'];


// ------------------------------------------------------------
// Build batch update requests
// ------------------------------------------------------------

$requests = [];


// ------------------------------------------------------------
// Add form description if provided
// ------------------------------------------------------------

if ($formDescription !== '') {

    $requests[] = [

        'updateFormInfo' => [

            'info' => [

                'description' => $formDescription

            ],

            'updateMask' => 'description'

        ]

    ];
}


// ------------------------------------------------------------
// Add questions
// ------------------------------------------------------------

foreach ($questions as $question) {

    if (!is_array($question)) {
        continue;
    }


    if (!isset($question['question'])) {
        continue;
    }


    $questionText = trim(
        (string) $question['question']
    );


    if ($questionText === '') {
        continue;
    }


    // --------------------------------------------------------
    // Question type
    // --------------------------------------------------------

    $type = strtolower(
        trim(
            (string) ($question['type'] ?? 'multiple_choice')
        )
    );


    // --------------------------------------------------------
    // Required
    // --------------------------------------------------------

    $required = true;

    if (isset($question['required'])) {

        $required = filter_var(
            $question['required'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($required === null) {
            $required = true;
        }
    }


    // --------------------------------------------------------
    // Multiple Choice / Radio
    // --------------------------------------------------------

    if (
        $type === 'multiple_choice' ||
        $type === 'multiple choice' ||
        $type === 'mcq' ||
        $type === 'radio'
    ) {

        $options = $question['options'] ?? [];

        if (!is_array($options) || count($options) === 0) {
            continue;
        }


        $choiceOptions = [];


        foreach ($options as $option) {

            if (is_array($option)) {

                $optionText = $option['text'] ?? '';

            } else {

                $optionText = $option;
            }


            $optionText = trim(
                (string) $optionText
            );


            if ($optionText === '') {
                continue;
            }


            $choiceOptions[] = [
                'value' => $optionText
            ];
        }


        if (count($choiceOptions) === 0) {
            continue;
        }


        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'choiceQuestion' => [

                                'type' => 'RADIO',

                                'options' => $choiceOptions,

                                'shuffle' => false

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Checkboxes / Multiple Select
    // --------------------------------------------------------
    elseif (
        $type === 'checkbox' ||
        $type === 'checkboxes' ||
        $type === 'multiple_select' ||
        $type === 'multiple select'
    ) {

        $options = $question['options'] ?? [];

        if (!is_array($options) || count($options) === 0) {
            continue;
        }


        $choiceOptions = [];


        foreach ($options as $option) {

            if (is_array($option)) {

                $optionText = $option['text'] ?? '';

            } else {

                $optionText = $option;
            }


            $optionText = trim(
                (string) $optionText
            );


            if ($optionText === '') {
                continue;
            }


            $choiceOptions[] = [
                'value' => $optionText
            ];
        }


        if (count($choiceOptions) === 0) {
            continue;
        }


        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'choiceQuestion' => [

                                'type' => 'CHECKBOX',

                                'options' => $choiceOptions,

                                'shuffle' => false

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Dropdown
    // --------------------------------------------------------
    elseif (
        $type === 'dropdown' ||
        $type === 'drop_down' ||
        $type === 'drop down' ||
        $type === 'select'
    ) {

        $options = $question['options'] ?? [];

        if (!is_array($options) || count($options) === 0) {
            continue;
        }


        $choiceOptions = [];


        foreach ($options as $option) {

            if (is_array($option)) {

                $optionText = $option['text'] ?? '';

            } else {

                $optionText = $option;
            }


            $optionText = trim(
                (string) $optionText
            );


            if ($optionText === '') {
                continue;
            }


            $choiceOptions[] = [
                'value' => $optionText
            ];
        }


        if (count($choiceOptions) === 0) {
            continue;
        }


        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'choiceQuestion' => [

                                'type' => 'DROP_DOWN',

                                'options' => $choiceOptions,

                                'shuffle' => false

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // True / False
    // --------------------------------------------------------
    elseif (
        $type === 'true_false' ||
        $type === 'true/false' ||
        $type === 'true false' ||
        $type === 'boolean'
    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'choiceQuestion' => [

                                'type' => 'RADIO',

                                'options' => [

                                    [
                                        'value' => 'True'
                                    ],

                                    [
                                        'value' => 'False'
                                    ]

                                ],

                                'shuffle' => false

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Short Answer
    // --------------------------------------------------------
    elseif (
        $type === 'short_answer' ||
        $type === 'short answer' ||
        $type === 'text' ||
        $type === 'email' ||
        $type === 'name' ||
        $type === 'phone'
    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'textQuestion' => [

                                'paragraph' => false

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Paragraph / Long Answer
    // --------------------------------------------------------
    elseif (
        $type === 'paragraph' ||
        $type === 'long_answer' ||
        $type === 'long answer' ||
        $type === 'essay' ||
        $type === 'textarea'
    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'textQuestion' => [

                                'paragraph' => true

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Linear Scale
    // --------------------------------------------------------
    elseif (
        $type === 'linear_scale' ||
        $type === 'linear scale' ||
        $type === 'scale' ||
        $type === 'rating'
    ) {

        $low = isset($question['low'])
            ? (int) $question['low']
            : 1;

        $high = isset($question['high'])
            ? (int) $question['high']
            : 5;


        // Google Forms supports scale values from 0-10.
        if ($low < 0) {
            $low = 0;
        }

        if ($low > 10) {
            $low = 10;
        }

        if ($high < 2) {
            $high = 2;
        }

        if ($high > 10) {
            $high = 10;
        }


        if ($high <= $low) {
            $high = $low + 1;
        }

        if ($high > 10) {
            $high = 10;
            $low = 9;
        }


        $scaleQuestion = [

            'low' => $low,

            'high' => $high

        ];


        if (
            isset($question['lowLabel']) &&
            trim((string) $question['lowLabel']) !== ''
        ) {

            $scaleQuestion['lowLabel'] = trim(
                (string) $question['lowLabel']
            );
        }


        if (
            isset($question['highLabel']) &&
            trim((string) $question['highLabel']) !== ''
        ) {

            $scaleQuestion['highLabel'] = trim(
                (string) $question['highLabel']
            );
        }


        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'scaleQuestion' => $scaleQuestion

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Date
    // --------------------------------------------------------
    elseif (
        $type === 'date'
    ) {

        $dateQuestion = [

            'includeYear' => true,

            'includeTime' => false

        ];


        if (isset($question['includeYear'])) {

            $dateQuestion['includeYear'] = filter_var(
                $question['includeYear'],
                FILTER_VALIDATE_BOOLEAN
            );
        }


        if (isset($question['includeTime'])) {

            $dateQuestion['includeTime'] = filter_var(
                $question['includeTime'],
                FILTER_VALIDATE_BOOLEAN
            );
        }


        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'dateQuestion' => $dateQuestion

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }


    // --------------------------------------------------------
    // Time
    // --------------------------------------------------------
    elseif (
        $type === 'time'
    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'timeQuestion' => [

                                'duration' => false

                            ]

                        ]

                    ]

                ],

                'location' => [

                    'index' => count($requests)

                ]

            ]

        ];
    }
}


// ------------------------------------------------------------
// Make sure at least one question was created
// ------------------------------------------------------------

if (count($requests) === 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'No valid questions could be created from the generated form.'
    ]);

    exit;
}


// ------------------------------------------------------------
// Add questions / form information to the form
// ------------------------------------------------------------

$batchUrl =
    'https://forms.googleapis.com/v1/forms/' .
    urlencode($formId) .
    ':batchUpdate';


$batchData = [

    'requests' => $requests

];


$ch = curl_init($batchUrl);


curl_setopt_array($ch, [

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HTTPHEADER => [

        'Authorization: Bearer ' . $accessToken,

        'Content-Type: application/json'

    ],

    CURLOPT_POSTFIELDS =>
        json_encode($batchData)

]);


$batchResponse = curl_exec($ch);

$batchHttpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$batchCurlError = curl_error($ch);

curl_close($ch);


if ($batchResponse === false) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'error' =>
            'Could not connect to Google Forms.',

        'details' =>
            $batchCurlError

    ]);

    exit;
}


$batchResult = json_decode(
    $batchResponse,
    true
);


if (
    $batchHttpCode < 200 ||
    $batchHttpCode >= 300
) {

    http_response_code($batchHttpCode);

    echo json_encode([

        'success' => false,

        'error' =>
            'Google Forms rejected the generated form.',

        'details' =>
            $batchResult

    ]);

    exit;
}


// ------------------------------------------------------------
// Success
// ------------------------------------------------------------

echo json_encode([

    'success' => true,

    'formId' => $formId,

    'formUrl' =>
        'https://docs.google.com/forms/d/' .
        $formId .
        '/edit',

    'message' =>
        'Google Form created successfully.'

]);

?>