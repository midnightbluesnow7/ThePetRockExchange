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

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$rockID = (int)($input['rockID'] ?? 0);
$cardID = (int)($input['cardID'] ?? 0);

if ($rockID <= 0 || $cardID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'rockID and cardID required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT rockID, price, listed FROM rocks WHERE rockID = :rid FOR UPDATE");
    $stmt->execute([':rid' => $rockID]);
    $rock = $stmt->fetch();

    if (!$rock || !$rock['listed']) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Rock not available.']);
        exit;
    }

    $stmt2 = $pdo->prepare("SELECT cc.cardID FROM custcards cc WHERE cc.cardID = :cid AND cc.custID = :cust LIMIT 1");
    $stmt2->execute([':cid' => $cardID, ':cust' => $custID]);
    $own = $stmt2->fetch();
    if (!$own) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'Card not associated with your account.']);
        exit;
    }

    $stmt3 = $pdo->prepare("INSERT INTO purchases (custID, rockID, cardID, amount) VALUES (:cust, :rock, :card, :amount)");
    $stmt3->execute([
        ':cust' => $custID,
        ':rock' => $rockID,
        ':card' => $cardID,
        ':amount' => $rock['price']
    ]);
    $purchaseID = $pdo->lastInsertId();

    $stmt4 = $pdo->prepare("UPDATE rocks SET owner_custID = :newOwner, listed = 0 WHERE rockID = :rid");
    $stmt4->execute([':newOwner' => $custID, ':rid' => $rockID]);

    $pdo->commit();
    echo json_encode(['success' => true, 'purchaseID' => (int)$purchaseID]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Purchase failed.']);
    error_log("Purchase error: " . $e->getMessage());
}
