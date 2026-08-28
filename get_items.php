<?php

require_once 'db.php';

header("Content-Type: application/json; charset=utf-8");

$isLoggedIn =
    !empty($_SESSION["logged_in"]) &&
    !empty($_SESSION["user_id"]);

if (!$isLoggedIn) {

    echo json_encode([
        "success" => true,
        "items" => []
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

try {

    $roleStmt = $pdo->prepare("
        SELECT role
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $roleStmt->execute([
        $_SESSION["user_id"]
    ]);

    $role = $roleStmt->fetchColumn() ?: "user";

    if ($role === "admin") {

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
            $_SESSION["user_id"]
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