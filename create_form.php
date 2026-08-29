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
// Get questions from the request (supports JSON body & $_POST)
// ------------------------------------------------------------

$inputData = null;
$rawInput = file_get_contents('php://input');

if (!empty($rawInput)) {
    $inputData = json_decode($rawInput, true);
}

$questions = null;

if (is_array($inputData)) {
    if (isset($inputData['questions']) && is_array($inputData['questions'])) {
        $questions = $inputData['questions'];
    } elseif (isset($inputData[0]) && is_array($inputData[0])) {
        $questions = $inputData;
    }
}

if ($questions === null) {
    $questionsJson = $_POST['questions'] ?? '';
    if (!empty($questionsJson)) {
        if (is_string($questionsJson)) {
            $questions = json_decode($questionsJson, true);
        } elseif (is_array($questionsJson)) {
            $questions = $questionsJson;
        }
    }
}

if (!is_array($questions)) {

    http_response_code(400);

    echo json_encode([

        'success' => false,

        'error' => 'No questions were provided.'

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
// OPTIONAL FORM INFORMATION
// ------------------------------------------------------------

$formTitle = $inputData['title'] ?? ($_POST['title'] ?? 'AI Generated Form');

$formDescription = $inputData['description'] ?? ($_POST['description'] ?? '');

$formTitle = trim((string) $formTitle);

$formDescription = trim((string) $formDescription);

if ($formTitle === '') {

    $formTitle = 'AI Generated Form';

}



// ------------------------------------------------------------
// ADDED: FORM SETTINGS
// ------------------------------------------------------------

$formConfirmationMessage =
    $inputData['confirmationMessage'] ?? ($_POST['confirmationMessage'] ?? '');

$formConfirmationMessage =
    trim((string) $formConfirmationMessage);



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

    $errMsg = $form['error']['message'] ?? 'Google Forms API returned an error.';

    if ($httpCode === 403 && (stripos($errMsg, 'scope') !== false || stripos($errMsg, 'permission') !== false || stripos($errMsg, 'insufficient') !== false)) {
        $errMsg = 'Google Forms permission is missing. Please reconnect your Google account.';
    }

    echo json_encode([

        'success' => false,

        'error' => $errMsg,

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
// Add form description
// ------------------------------------------------------------

// ADDED:
// Google Forms supports updating the form description separately.
// ------------------------------------------------------------

if ($formDescription !== '') {

    $descriptionRequest = [

        'updateFormInfo' => [

            'info' => [

                'description' => $formDescription

            ],

            'updateMask' => 'description'

        ]

    ];



    $descriptionUrl =

        'https://forms.googleapis.com/v1/forms/' .

        urlencode($formId) .

        ':batchUpdate';



    $descriptionData = [

        'requests' => [

            $descriptionRequest

        ]

    ];



    $ch = curl_init($descriptionUrl);



    curl_setopt_array($ch, [

        CURLOPT_POST => true,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [

            'Authorization: Bearer ' . $accessToken,

            'Content-Type: application/json'

        ],

        CURLOPT_POSTFIELDS => json_encode($descriptionData)

    ]);



    $descriptionResponse = curl_exec($ch);

    $descriptionHttpCode = curl_getinfo(

        $ch,

        CURLINFO_HTTP_CODE

    );

    $descriptionCurlError = curl_error($ch);

    curl_close($ch);



    if ($descriptionResponse === false) {

        http_response_code(500);

        echo json_encode([

            'success' => false,

            'error' => 'Could not connect to Google Forms.',

            'details' => $descriptionCurlError

        ]);

        exit;

    }



    $descriptionResult = json_decode(

        $descriptionResponse,

        true

    );



    if (

        $descriptionHttpCode < 200 ||

        $descriptionHttpCode >= 300

    ) {

        http_response_code($descriptionHttpCode);

        $descErrMsg = $descriptionResult['error']['message'] ?? 'Google Forms rejected the form description.';

        if ($descriptionHttpCode === 403 && (stripos($descErrMsg, 'scope') !== false || stripos($descErrMsg, 'permission') !== false || stripos($descErrMsg, 'insufficient') !== false)) {
            $descErrMsg = 'Google Forms permission is missing. Please reconnect your Google account.';
        }

        echo json_encode([

            'success' => false,

            'error' => $descErrMsg,

            'details' => $descriptionResult

        ]);

        exit;

    }

}



// ------------------------------------------------------------
// Add questions
// ------------------------------------------------------------

$requests = [];



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



    $type = strtolower(

        trim($question['type'] ?? 'multiple_choice')

    );



    // --------------------------------------------------------
// Required setting
// --------------------------------------------------------

    // AI can decide whether each question is required.

    $required = true;



    if (isset($question['required'])) {

        $required = (bool) $question['required'];

    }



    // --------------------------------------------------------
// Multiple Choice
// --------------------------------------------------------

    if (

        $type === 'multiple_choice' ||

        $type === 'multiple choice' ||

        $type === 'mcq' ||

        $type === 'radio'

    ) {

        $options = $question['options'] ?? $question['choices'] ?? [];



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



            $optionText = trim((string) $optionText);



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
// Checkboxes
// --------------------------------------------------------
    elseif (

        $type === 'checkboxes' ||

        $type === 'checkbox' ||

        $type === 'checkbox'

    ) {

        $options = $question['options'] ?? $question['choices'] ?? [];



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



            $optionText = trim((string) $optionText);



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

        $options = $question['options'] ?? $question['choices'] ?? [];



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



            $optionText = trim((string) $optionText);



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
    else if (

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

        $type === 'text_short' ||

        $type === 'name' ||

        $type === 'email' ||

        $type === 'phone' ||

        $type === 'number'

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

        $type === 'textarea' ||

        $type === 'long_text'

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
// Date
// --------------------------------------------------------
    elseif (

        $type === 'date'

    ) {

        $requests[] = [

            'createItem' => [

                'item' => [

                    'title' => $questionText,

                    'questionItem' => [

                        'question' => [

                            'requiredQuestion' => $required,

                            'dateQuestion' => [

                                'includeYear' => true,

                                'includeTime' => false

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



    // --------------------------------------------------------
// Unsupported type
// --------------------------------------------------------
//
// ADDED:
// If the AI sends a question type that this file does not
// understand yet, don't crash the entire form creation.
// Instead, treat it as a short-answer question.
//
// This makes the generator more flexible for general-purpose
// forms such as registration, interest, signup, surveys, etc.
// --------------------------------------------------------
    else {

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

        $batchErrMsg = $batchResult['error']['message'] ?? 'Google Forms rejected the questions.';

        if ($batchHttpCode === 403 && (stripos($batchErrMsg, 'scope') !== false || stripos($batchErrMsg, 'permission') !== false || stripos($batchErrMsg, 'insufficient') !== false)) {
            $batchErrMsg = 'Google Forms permission is missing. Please reconnect your Google account.';
        }

        echo json_encode([

            'success' => false,

            'error' => $batchErrMsg,

            'details' => $batchResult

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

    'title' => $formTitle,

    'description' => $formDescription,

    'message' =>

        'Google Form created successfully.'

]);

?>