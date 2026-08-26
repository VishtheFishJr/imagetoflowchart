<?php

require_once 'db.php';

header("Content-Type: application/json; charset=utf-8");

try {

    $stmt = $pdo->query("
        SELECT
            id,
            name,
            type,
            created_at,
            updated_at,
            presentation_url
        FROM generated_items
        ORDER BY updated_at DESC
    ");

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