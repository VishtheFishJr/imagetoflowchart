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

        'title' => 'AI Study Scanner Quiz',

        'documentTitle' => 'AI Study Scanner Quiz'

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
// Add questions
// ------------------------------------------------------------

$requests = [];


foreach ($questions as $question) {

    if (!isset($question['question'])) {
        continue;
    }


    $questionText = trim(
        $question['question']
    );


    if ($questionText === '') {
        continue;
    }


    $type = strtolower(
        $question['type'] ?? 'multiple_choice'
    );


    // --------------------------------------------------------
    // Multiple Choice
    // --------------------------------------------------------

    if (
        $type === 'multiple_choice' ||
        $type === 'multiple choice' ||
        $type === 'mcq'
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


            if ($optionText === '') {
                continue;
            }


            $choiceOptions[] = [

                'value' => (string) $optionText

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

                            'requiredQuestion' => true,

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
    // True / False
    // --------------------------------------------------------
    elseif (
        $type === 'true_false' ||
        $type === 'true/false' ||
        $type === 'true false'
    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => true,

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
        $type === 'text'
    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => true,

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

}


// ------------------------------------------------------------
// Add questions to the form
// ------------------------------------------------------------

if (count($requests) > 0) {

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
                'Google Forms rejected the questions.',

            'details' =>
                $batchResult

        ]);

        exit;
    }

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