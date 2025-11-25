<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_session.php'; // maybe optional
require_once __DIR__ . '/setRockSession.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT rockID, name, description, price, owner_custID, listed FROM rocks WHERE listed = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $rocks = $stmt->fetchAll();

    echo json_encode(['rocks' => $rocks]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch rocks.']);
    error_log("List rocks error: " . $e->getMessage());
}