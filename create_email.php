<?php

require_once 'vendor/autoload.php';
require_once 'db.php';

session_name("PHPSESSID");
session_start();

header("Content-Type: application/json; charset=utf-8");

// ---------------------------------------------------------
// HELPER: JSON RESPONSE
// ---------------------------------------------------------

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

// ---------------------------------------------------------
// CHECK GOOGLE CONNECTION
// ---------------------------------------------------------

if (!isset($_SESSION["google_token"])) {
    jsonResponse([
        "success" => false,
        "error" => "Google account not connected"
    ], 401);
}

$client = new Google_Client();

if (file_exists("client_secret.json")) {
    $client->setAuthConfig("client_secret.json");
}

$client->setAccessToken($_SESSION["google_token"]);

if ($client->isAccessTokenExpired()) {
    $refreshToken = $client->getRefreshToken();
    if ($refreshToken) {
        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (isset($newToken['error'])) {
            jsonResponse([
                "success" => false,
                "error" => "Google authorization expired. Please reconnect your Google account."
            ], 401);
        }
        $_SESSION['google_token'] = $client->getAccessToken();
    } else {
        jsonResponse([
            "success" => false,
            "error" => "Google token expired. Please reconnect your Google account."
        ], 401);
    }
}

// ---------------------------------------------------------
// READ INPUT DATA
// ---------------------------------------------------------

$rawInput = file_get_contents("php://input");
$inputData = null;

if (!empty($rawInput)) {
    $inputData = json_decode($rawInput, true);
}

if (!is_array($inputData)) {
    jsonResponse([
        "success" => false,
        "error" => "No valid email data provided."
    ], 400);
}

$subject = trim((string)($inputData['subject'] ?? 'AI Generated Email'));
$to = trim((string)($inputData['to'] ?? ($inputData['recipient'] ?? '')));
$body = (string)($inputData['body'] ?? '');

if ($subject === '') {
    $subject = 'AI Generated Email';
}

// ---------------------------------------------------------
// BUILD GMAIL DRAFT
// ---------------------------------------------------------

try {
    $gmailService = new Google_Service_Gmail($client);

    $rawMessageString = "";
    if ($to !== '') {
        $rawMessageString .= "To: " . $to . "\r\n";
    }
    $rawMessageString .= "Subject: " . $subject . "\r\n";
    $rawMessageString .= "MIME-Version: 1.0\r\n";
    $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $rawMessageString .= nl2br($body);

    $rawMessage = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');

    $message = new Google_Service_Gmail_Message();
    $message->setRaw($rawMessage);

    $draft = new Google_Service_Gmail_Draft();
    $draft->setMessage($message);

    $createdDraft = $gmailService->users_drafts->create('me', $draft);
    $draftId = $createdDraft->getId();

    $draftUrl = "https://mail.google.com/mail/u/0/#drafts";

    // ---------------------------------------------------------
    // UPDATE DATABASE RECORD
    // ---------------------------------------------------------

    $userId = $_SESSION["user_id"] ?? null;
    $itemId = $inputData["item_id"] ?? $inputData["id"] ?? null;

    if (isset($GLOBALS["pdo"]) && $GLOBALS["pdo"] instanceof PDO) {
        if ($itemId) {
            $stmt = $GLOBALS["pdo"]->prepare(
                "UPDATE generated_items SET presentation_url = ? WHERE id = ?"
            );
            $stmt->execute([$draftUrl, $itemId]);
        } else {
            if ($userId !== null) {
                $stmt = $GLOBALS["pdo"]->prepare(
                    "UPDATE generated_items 
                     SET presentation_url = ? 
                     WHERE type = 'email' AND user_id = ? AND presentation_url IS NULL 
                     ORDER BY id DESC LIMIT 1"
                );
                $stmt->execute([$draftUrl, $userId]);
            } else {
                $stmt = $GLOBALS["pdo"]->prepare(
                    "UPDATE generated_items 
                     SET presentation_url = ? 
                     WHERE type = 'email' AND user_id IS NULL AND presentation_url IS NULL 
                     ORDER BY id DESC LIMIT 1"
                );
                $stmt->execute([$draftUrl]);
            }
        }
    }

    jsonResponse([
        "success" => true,
        "draftId" => $draftId,
        "url" => $draftUrl,
        "subject" => $subject,
        "message" => "Email draft created successfully in Gmail."
    ]);

} catch (Exception $e) {
    $errMsg = $e->getMessage();
    if (
        stripos($errMsg, 'scope') !== false ||
        stripos($errMsg, 'permission') !== false ||
        stripos($errMsg, '403') !== false ||
        stripos($errMsg, 'insufficient') !== false
    ) {
        $errMsg = 'Gmail permission is missing. Please reconnect your Google account.';
    }

    jsonResponse([
        "success" => false,
        "error" => $errMsg,
        "details" => $e->getMessage()
    ], 500);
}
?>
