<?php

require_once 'db.php';

header("Content-Type: application/json; charset=utf-8");

try {

    /*
     * Make sure the user is logged in.
     */
    if (
        !isset($_SESSION["logged_in"]) ||
        $_SESSION["logged_in"] !== true ||
        !isset($_SESSION["user_id"])
    ) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "error" => "Not logged in."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $userId = $_SESSION["user_id"];

    /*
     * Admins can see every generated item.
     * Regular users can only see items they created.
     */
    if (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "admin"
    ) {

        $stmt = $pdo->query("
            SELECT
                id,
                name,
                type,
                created_at,
                updated_at,
                presentation_url,
                user_id
            FROM generated_items
            ORDER BY updated_at DESC
        ");

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                type,
                created_at,
                updated_at,
                presentation_url,
                user_id
            FROM generated_items
            WHERE user_id = ?
            ORDER BY updated_at DESC
        ");

        $stmt->execute([
            $userId
        ]);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "items" => $items
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>