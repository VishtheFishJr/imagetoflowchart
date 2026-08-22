<?php

require_once 'db.php';

header("Content-Type: application/json");

try {

    $stmt = $pdo->query("
        SELECT
            id,
            name,
            type,
            created_at,
            updated_at
        FROM generated_items
        ORDER BY updated_at DESC
    ");

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "items" => $items
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);

}