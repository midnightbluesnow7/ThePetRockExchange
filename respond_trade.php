<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_session.php';
require_once __DIR__ . '/setRockSession.php';

header('Content-Type: application/json');
$user = get_current_custID();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$tradeID = (int)($input['tradeID'] ?? 0);
$action = $input['action'] ?? '';

if ($tradeID <= 0 || !in_array($action, ['accept', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'tradeID and valid action required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM trades WHERE tradeID = :tid FOR UPDATE");
    $stmt->execute([':tid' => $tradeID]);
    $trade = $stmt->fetch();
    if (!$trade) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Trade not found.']);
        exit;
    }
    if ((int)$trade['to_custID'] !== $user) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to respond.']);
        exit;
    }

    if ($action === 'reject') {
        $stmt2 = $pdo->prepare("UPDATE trades SET status = 'rejected', updated_at = NOW() WHERE tradeID = :tid");
        $stmt2->execute([':tid' => $tradeID]);
        $pdo->commit();
        echo json_encode(['success' => true, 'status' => 'rejected']);
        exit;
    }

    // accepting
    $stmt3 = $pdo->prepare("SELECT rockID, owner_custID FROM rocks WHERE rockID = :rid FOR UPDATE");
    $stmt3->execute([':rid' => $trade['rockID']]);
    $rock = $stmt3->fetch();
    if (!$rock || (int)$rock['owner_custID'] !== $user) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Rock no longer available for trade.']);
        exit;
    }

    $stmt4 = $pdo->prepare("UPDATE rocks SET owner_custID = :newOwner, listed = 0 WHERE rockID = :rid");
    $stmt4->execute([':newOwner' => $trade['from_custID'], ':rid' => $trade['rockID']]);

    $stmt5 = $pdo->prepare("UPDATE trades SET status = 'completed', updated_at = NOW() WHERE tradeID = :tid");
    $stmt5->execute([':tid' => $tradeID]);

    $pdo->commit();
    echo json_encode(['success' => true, 'status' => 'completed']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Could not respond to trade.']);
    error_log("Respond trade error: " . $e->getMessage());
}
