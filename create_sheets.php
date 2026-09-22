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
// Get request data
// Supports JSON body and $_POST
// ------------------------------------------------------------

$inputData = null;

$rawInput = file_get_contents('php://input');

if (!empty($rawInput)) {

    $inputData = json_decode($rawInput, true);
}


// ------------------------------------------------------------
// Get spreadsheet title
// ------------------------------------------------------------

$spreadsheetTitle =
    $inputData['title'] ??
    ($_POST['title'] ?? 'AI Generated Spreadsheet');

$spreadsheetTitle = trim((string) $spreadsheetTitle);

if ($spreadsheetTitle === '') {

    $spreadsheetTitle = 'AI Generated Spreadsheet';
}


// ------------------------------------------------------------
// Get sheet/tab name
// ------------------------------------------------------------

$sheetName =
    $inputData['sheetName'] ??
    ($_POST['sheetName'] ?? 'Sheet1');

$sheetName = trim((string) $sheetName);

if ($sheetName === '') {

    $sheetName = 'Sheet1';
}


// ------------------------------------------------------------
// Get image path
// ------------------------------------------------------------

$imagePath =
    $inputData['image_path'] ??
    ($_POST['image_path'] ?? '');

$imagePath = trim((string) $imagePath);


// ------------------------------------------------------------
// Get spreadsheet data
//
// Expected format:
//
// [
//     ["Column 1", "Column 2"],
//     ["Value 1", "Value 2"],
//     ["Value 3", "Value 4"]
// ]
// ------------------------------------------------------------

$sheetData = null;


// JSON body
if (is_array($inputData)) {

    if (
        isset($inputData['data']) &&
        is_array($inputData['data'])
    ) {

        $sheetData = $inputData['data'];

    } elseif (
        isset($inputData['values']) &&
        is_array($inputData['values'])
    ) {

        $sheetData = $inputData['values'];
    }
}


// $_POST fallback
if ($sheetData === null) {

    $dataJson = $_POST['data'] ?? '';

    if (!empty($dataJson)) {

        if (is_string($dataJson)) {

            $sheetData = json_decode(
                $dataJson,
                true
            );

        } elseif (is_array($dataJson)) {

            $sheetData = $dataJson;
        }
    }
}


// ------------------------------------------------------------
// Validate spreadsheet data
// ------------------------------------------------------------

if (!is_array($sheetData)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'No spreadsheet data was provided.'
    ]);

    exit;
}


if (count($sheetData) === 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'No spreadsheet rows were found.'
    ]);

    exit;
}


// ------------------------------------------------------------
// Make sure every row is an array
// ------------------------------------------------------------

$cleanData = [];

foreach ($sheetData as $row) {

    if (!is_array($row)) {
        continue;
    }

    $cleanRow = [];

    foreach ($row as $cell) {

        if (is_array($cell) || is_object($cell)) {

            $cleanRow[] = json_encode(
                $cell,
                JSON_UNESCAPED_UNICODE
            );

        } elseif ($cell === null) {

            $cleanRow[] = '';

        } else {

            $cleanRow[] = (string) $cell;
        }
    }

    $cleanData[] = $cleanRow;
}


if (count($cleanData) === 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'No valid spreadsheet rows were found.'
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

$client->setAccessToken(
    $_SESSION['google_token']
);


// ------------------------------------------------------------
// Refresh expired token if possible
// ------------------------------------------------------------

if ($client->isAccessTokenExpired()) {

    $refreshToken = $client->getRefreshToken();

    if ($refreshToken) {

        $newToken =
            $client->fetchAccessTokenWithRefreshToken(
                $refreshToken
            );

        if (isset($newToken['error'])) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' =>
                    'Google authorization expired. Please reconnect your Google account.'
            ]);

            exit;
        }

        $_SESSION['google_token'] =
            $client->getAccessToken();

    } else {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' =>
                'Google authorization expired. Please reconnect your Google account.'
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
// Create Google Spreadsheet
// ------------------------------------------------------------

$spreadsheetData = [

    'properties' => [

        'title' => $spreadsheetTitle
    ],

    'sheets' => [

        [
            'properties' => [

                'title' => $sheetName
            ]
        ]
    ]
];


$ch = curl_init(
    'https://sheets.googleapis.com/v4/spreadsheets'
);

curl_setopt_array($ch, [

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HTTPHEADER => [

        'Authorization: Bearer ' . $accessToken,

        'Content-Type: application/json'
    ],

    CURLOPT_POSTFIELDS =>
        json_encode($spreadsheetData)

]);


$response = curl_exec($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$curlError = curl_error($ch);

curl_close($ch);


// ------------------------------------------------------------
// Handle connection failure
// ------------------------------------------------------------

if ($response === false) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'error' =>
            'Could not connect to Google Sheets.',

        'details' =>
            $curlError
    ]);

    exit;
}


// ------------------------------------------------------------
// Decode spreadsheet response
// ------------------------------------------------------------

$spreadsheet = json_decode(
    $response,
    true
);


// ------------------------------------------------------------
// Handle Google API errors
// ------------------------------------------------------------

if (
    $httpCode < 200 ||
    $httpCode >= 300
) {

    http_response_code($httpCode);

    $errMsg =
        $spreadsheet['error']['message'] ??
        'Google Sheets API returned an error.';


    if (
        $httpCode === 403 &&
        (
            stripos($errMsg, 'scope') !== false ||
            stripos($errMsg, 'permission') !== false ||
            stripos($errMsg, 'insufficient') !== false
        )
    ) {

        $errMsg =
            'Google Sheets permission is missing. Please reconnect your Google account.';
    }


    echo json_encode([

        'success' => false,

        'error' => $errMsg,

        'details' => $spreadsheet
    ]);

    exit;
}


// ------------------------------------------------------------
// Make sure Google returned a spreadsheet ID
// ------------------------------------------------------------

if (!isset($spreadsheet['spreadsheetId'])) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'error' =>
            'Google did not return a spreadsheet ID.',

        'details' =>
            $spreadsheet
    ]);

    exit;
}


$spreadsheetId =
    $spreadsheet['spreadsheetId'];


// ------------------------------------------------------------
// Determine actual sheet name
// ------------------------------------------------------------
//
// Google should use the requested sheet name,
// but get the returned value when available.
//

$actualSheetName = $sheetName;

if (
    isset($spreadsheet['sheets'][0]['properties']['title'])
) {

    $actualSheetName =
        $spreadsheet['sheets'][0]['properties']['title'];
}


// ------------------------------------------------------------
// Calculate spreadsheet range
// ------------------------------------------------------------
//
// Convert a column number into A1 notation.
//
// Example:
// 1  -> A
// 26 -> Z
// 27 -> AA
// ------------------------------------------------------------

function columnToLetter($columnNumber)
{
    $letter = '';

    while ($columnNumber > 0) {

        $remainder =
            ($columnNumber - 1) % 26;

        $letter =
            chr(65 + $remainder) . $letter;

        $columnNumber =
            intdiv(
                $columnNumber - 1,
                26
            );
    }

    return $letter;
}


$maxColumns = 0;

foreach ($cleanData as $row) {

    $columnCount = count($row);

    if ($columnCount > $maxColumns) {

        $maxColumns = $columnCount;
    }
}


$maxRows = count($cleanData);


if ($maxColumns < 1) {
    $maxColumns = 1;
}


if ($maxRows < 1) {
    $maxRows = 1;
}


$lastColumn =
    columnToLetter($maxColumns);


$range =
    "'" .
    str_replace(
        "'",
        "''",
        $actualSheetName
    ) .
    "'!A1:" .
    $lastColumn .
    $maxRows;


// ------------------------------------------------------------
// Write data into spreadsheet
// ------------------------------------------------------------

$valueData = [

    'range' => $range,

    'majorDimension' => 'ROWS',

    'values' => $cleanData
];


$encodedRange =
    rawurlencode($range);


$valuesUrl =
    'https://sheets.googleapis.com/v4/spreadsheets/' .
    rawurlencode($spreadsheetId) .
    '/values/' .
    $encodedRange .
    '?valueInputOption=USER_ENTERED';


$ch = curl_init($valuesUrl);

curl_setopt_array($ch, [

    CURLOPT_CUSTOMREQUEST => 'PUT',

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HTTPHEADER => [

        'Authorization: Bearer ' . $accessToken,

        'Content-Type: application/json'
    ],

    CURLOPT_POSTFIELDS =>
        json_encode($valueData)

]);


$valuesResponse = curl_exec($ch);

$valuesHttpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$valuesCurlError = curl_error($ch);

curl_close($ch);


// ------------------------------------------------------------
// Handle connection failure
// ------------------------------------------------------------

if ($valuesResponse === false) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'error' =>
            'Could not connect to Google Sheets while writing data.',

        'details' =>
            $valuesCurlError
    ]);

    exit;
}


// ------------------------------------------------------------
// Decode values response
// ------------------------------------------------------------

$valuesResult = json_decode(
    $valuesResponse,
    true
);


// ------------------------------------------------------------
// Handle write errors
// ------------------------------------------------------------

if (
    $valuesHttpCode < 200 ||
    $valuesHttpCode >= 300
) {

    http_response_code($valuesHttpCode);

    $errMsg =
        $valuesResult['error']['message'] ??
        'Google Sheets rejected the spreadsheet data.';


    if (
        $valuesHttpCode === 403 &&
        (
            stripos($errMsg, 'scope') !== false ||
            stripos($errMsg, 'permission') !== false ||
            stripos($errMsg, 'insufficient') !== false
        )
    ) {

        $errMsg =
            'Google Sheets permission is missing. Please reconnect your Google account.';
    }


    echo json_encode([

        'success' => false,

        'error' => $errMsg,

        'details' => $valuesResult
    ]);

    exit;
}


// ------------------------------------------------------------
// Google Sheets URL
// ------------------------------------------------------------

$spreadsheetUrl =
    'https://docs.google.com/spreadsheets/d/' .
    $spreadsheetId .
    '/edit';


// ------------------------------------------------------------
// Success
// ------------------------------------------------------------

echo json_encode([

    'success' => true,

    'spreadsheetId' =>
        $spreadsheetId,

    'url' =>
        $spreadsheetUrl,

    'spreadsheetUrl' =>
        $spreadsheetUrl,

    'title' =>
        $spreadsheetTitle,

    'sheetName' =>
        $actualSheetName,

    'range' =>
        $range,

    'data' =>
        $cleanData,

    'image_path' =>
        $imagePath,

    'spreadsheet_data' => json_encode([

        'title' =>
            $spreadsheetTitle,

        'sheetName' =>
            $actualSheetName,

        'range' =>
            $range,

        'data' =>
            $cleanData
    ], JSON_UNESCAPED_UNICODE),

    'message' =>
        'Google Sheet created successfully.'
]);

?>