<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'POST request required.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get questions
|--------------------------------------------------------------------------
|
| Expected format:
|
| [
|   {
|     "question": "What is photosynthesis?",
|     "type": "multiple_choice",
|     "options": ["A", "B", "C", "D"],
|     "answer": "B"
|   },
|   {
|     "question": "The mitochondria produces ATP.",
|     "type": "true_false",
|     "answer": "True"
|   },
|   {
|     "question": "What is the powerhouse of the cell?",
|     "type": "short_answer"
|   }
| ]
|
*/

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
        'error' => 'The question list is empty.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Google Client
|--------------------------------------------------------------------------
*/

$client = new Google_Client();

$client->setApplicationName('AI Study Scanner');

$client->setAuthConfig(__DIR__ . '/client_secret.json');

$client->setAccessType('offline');

$client->setPrompt('consent');

/*
|--------------------------------------------------------------------------
| Get the user's Google access token
|--------------------------------------------------------------------------
|
| This assumes google_login.php stores the OAuth token in:
|
| $_SESSION['google_access_token']
|
*/

if (!isset($_SESSION['google_access_token'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'Google account is not connected. Please connect your Google account first.'
    ]);

    exit;
}

$accessToken = $_SESSION['google_access_token'];

$client->setAccessToken($accessToken);

/*
|--------------------------------------------------------------------------
| Refresh expired token
|--------------------------------------------------------------------------
*/

if ($client->isAccessTokenExpired()) {

    $refreshToken = $client->getRefreshToken();

    if (!$refreshToken) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' => 'Google authorization has expired. Please reconnect your Google account.'
        ]);

        exit;
    }

    $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

    if (isset($newToken['error'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' => 'Unable to refresh Google authorization.',
            'details' => $newToken
        ]);

        exit;
    }

    $_SESSION['google_access_token'] = $client->getAccessToken();
}

/*
|--------------------------------------------------------------------------
| Google Forms API
|--------------------------------------------------------------------------
*/

try {

    /*
     * Google Forms API doesn't have a PHP-specific package in every
     * Google API client installation, so we use the authenticated
     * Google API endpoint directly.
     */

    $accessToken = $client->getAccessToken()['access_token'];

    /*
     |--------------------------------------------------------------------------
     | Create the Form
     |--------------------------------------------------------------------------
     */

    $createUrl = 'https://forms.googleapis.com/v1/forms';

    $formData = [
        'info' => [
            'title' => 'AI Study Scanner Quiz',
            'documentTitle' => 'AI Study Scanner Quiz'
        ]
    ];

    $ch = curl_init($createUrl);

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

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new Exception('cURL error: ' . $curlError);
    }

    $form = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {

        http_response_code($httpCode);

        echo json_encode([
            'success' => false,
            'error' => 'Google Forms API error.',
            'details' => $form
        ]);

        exit;
    }

    if (!isset($form['formId'])) {
        throw new Exception('Google did not return a form ID.');
    }

    $formId = $form['formId'];

    /*
     |--------------------------------------------------------------------------
     | Add Questions
     |--------------------------------------------------------------------------
     */

    $requests = [];

    /*
     * Google Forms requires the first question to be inserted
     * at index 0. We therefore insert questions in reverse order.
     */

    $questionCount = count($questions);

    foreach (array_reverse($questions) as $index => $question) {

        if (!isset($question['question'])) {
            continue;
        }

        $questionText = trim($question['question']);

        if ($questionText === '') {
            continue;
        }

        $type = strtolower(
            $question['type'] ??
            'multiple_choice'
        );

        /*
         |--------------------------------------------------------------------------
         | Multiple Choice
         |--------------------------------------------------------------------------
         */

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
                        'index' => 0
                    ]
                ]
            ];
        }

        /*
         |--------------------------------------------------------------------------
         | True / False
         |--------------------------------------------------------------------------
         */ elseif (
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
                        'index' => 0
                    ]
                ]
            ];
        }

        /*
         |--------------------------------------------------------------------------
         | Short Answer
         |--------------------------------------------------------------------------
         */ elseif (
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
                        'index' => 0
                    ]
                ]
            ];
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Send batchUpdate to Google Forms
     |--------------------------------------------------------------------------
     */

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
            CURLOPT_POSTFIELDS => json_encode($batchData)
        ]);

        $batchResponse = curl_exec($ch);

        $batchHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $batchCurlError = curl_error($ch);

        curl_close($ch);

        if ($batchResponse === false) {
            throw new Exception(
                'cURL error while adding questions: ' .
                $batchCurlError
            );
        }

        $batchResult = json_decode($batchResponse, true);

        if ($batchHttpCode < 200 || $batchHttpCode >= 300) {

            http_response_code($batchHttpCode);

            echo json_encode([
                'success' => false,
                'error' => 'Google Forms rejected the questions.',
                'details' => $batchResult
            ]);

            exit;
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Return Form
     |--------------------------------------------------------------------------
     */

    echo json_encode([
        'success' => true,
        'formId' => $formId,
        'formUrl' => 'https://docs.google.com/forms/d/' . $formId . '/edit',
        'message' => 'Google Form created successfully.'
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>