<?php

require_once 'db.php';

header("Content-Type: application/json");

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$id = intval($data["id"] ?? 0);
$name = trim($data["name"] ?? "");

if ($id <= 0 || $name === "") {

    echo json_encode([
        "success" => false,
        "error" => "Invalid item or name."
    ]);

    exit;
}

if (strlen($name) > 255) {

    echo json_encode([
        "success" => false,
        "error" => "Name is too long."
    ]);

    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE generated_items
        SET name = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $name,
        $id
    ]);

    echo json_encode([
        "success" => true
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);

}