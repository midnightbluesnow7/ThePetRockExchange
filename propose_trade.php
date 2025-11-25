<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_session.php';
require_once __DIR__ . '/setRockSession.php';

header('Content-Type: application/json');
$fromCustID = get_current_custID();
if (!$fromCustID) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$targetCustID = (int)($input['to_custID'] ?? 0);
$rockID = (int)($input['rockID'] ?? 0);

if ($targetCustID <= 0 || $rockID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'to_custID and rockID required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT rockID, owner_custID FROM rocks WHERE rockID = :rid FOR UPDATE");
    $stmt->execute([':rid' => $rockID]);
    $rock = $stmt->fetch();
    if (!$rock || (int)$rock['owner_custID'] !== $targetCustID) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Target does not own this rock.']);
        exit;
    }

    $stmt2 = $pdo->prepare("INSERT INTO trades (from_custID, to_custID, rockID, status) VALUES (:from, :to, :rock, 'proposed')");
    $stmt2->execute([
        ':from' => $fromCustID,
        ':to' => $targetCustID,
        ':rock' => $rockID
    ]);
    $tradeID = (int)$pdo->lastInsertId();

    $pdo->commit();
    echo json_encode(['success' => true, 'tradeID' => $tradeID]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Could not propose trade.']);
    error_log("Propose trade error: " . $e->getMessage());
}
