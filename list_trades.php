<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_session.php';
require_once __DIR__ . '/setRockSession.php';

header('Content-Type: application/json');
$custID = get_current_custID();
if (!$custID) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.tradeID, t.from_custID, t.to_custID, t.rockID, r.name AS rock_name, t.status, t.created_at, t.updated_at
        FROM trades t
        JOIN rocks r ON t.rockID = r.rockID
        WHERE t.from_custID = :id OR t.to_custID = :id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([':id' => $custID]);
    $trades = $stmt->fetchAll();

    echo json_encode(['trades' => $trades]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not list trades.']);
    error_log("List trades error: " . $e->getMessage());
}
