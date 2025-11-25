<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_session.php';
require_once __DIR__ . '/setRockSession.php';

header('Content-Type: application/json');

// check_session.php should verify user is logged; maybe provides a function like get_current_custID()
$custID = get_current_custID();
if (!$custID) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$card_last4 = preg_replace('/\D/', '', $input['card_last4'] ?? '');
$card_type = trim($input['card_type'] ?? '');
$exp_month = (int)($input['exp_month'] ?? 0);
$exp_year = (int)($input['exp_year'] ?? 0);
$token = trim($input['token'] ?? '');

if (strlen($card_last4) !== 4 || $exp_month < 1 || $exp_month > 12 || $exp_year < date('Y')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid card data.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO cards (card_last4, card_type, exp_month, exp_year, token) VALUES (:last4, :type, :m, :y, :token)");
    $stmt->execute([
        ':last4' => $card_last4,
        ':type' => $card_type,
        ':m' => $exp_month,
        ':y' => $exp_year,
        ':token' => $token
    ]);
    $cardID = (int)$pdo->lastInsertId();

    $stmt2 = $pdo->prepare("INSERT INTO custcards (custID, cardID, nickname) VALUES (:cust, :card, :nick)");
    $stmt2->execute([
        ':cust' => $custID,
        ':card' => $cardID,
        ':nick' => substr($card_type . ' ' . $card_last4, 0, 100)
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'cardID' => $cardID]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Could not add card.']);
    error_log("Add card error: " . $e->getMessage());
}
