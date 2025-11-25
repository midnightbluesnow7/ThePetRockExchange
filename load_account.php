<?php
header("Content-Type: application/json");
session_start();

// Ensure user is logged in
if (!isset($_SESSION['custID'])) {
    echo json_encode([
        "success" => false,
        "message" => "No active session. User not logged in."
    ]);
    exit();
}

require "db.php";  // Connects using your existing DB file

$custID = intval($_SESSION['custID']);

// ----------------------------
// 1. Load Customer Information
// ----------------------------
$customerQuery = $conn->prepare("
    SELECT custID, firstName, lastName, email 
    FROM customers 
    WHERE custID = ?
");
$customerQuery->bind_param("i", $custID);
$customerQuery->execute();
$customerResult = $customerQuery->get_result();

if ($customerResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Customer not found."
    ]);
    exit();
}

$customer = $customerResult->fetch_assoc();

// ----------------------------
// 2. Load Customer Card Info
// ----------------------------
$cardQuery = $conn->prepare("
    SELECT cardID, cardNumber, expMonth, expYear 
    FROM custcard 
    WHERE custID = ?
");
$cardQuery->bind_param("i", $custID);
$cardQuery->execute();
$cardResult = $cardQuery->get_result();

$cards = [];
while ($row = $cardResult->fetch_assoc()) {
    $cards[] = $row;
}

// ----------------------------
// 3. Load Rocks Owned
// ----------------------------
$rocksOwnedQuery = $conn->prepare("
    SELECT rockID, rockName, rockType, value 
    FROM rocks 
    WHERE ownerID = ?
");
$rocksOwnedQuery->bind_param("i", $custID);
$rocksOwnedQuery->execute();
$rocksOwnedResult = $rocksOwnedQuery->get_result();

$rocksOwned = [];
while ($row = $rocksOwnedResult->fetch_assoc()) {
    $rocksOwned[] = $row;
}

// ----------------------------
// 4. Load Rocks Listed for Trade/Sale
// ----------------------------
$listingsQuery = $conn->prepare("
    SELECT listingID, rockID, price, tradeable, status 
    FROM listings 
    WHERE custID = ?
");
$listingsQuery->bind_param("i", $custID);
$listingsQuery->execute();
$listingsResult = $listingsQuery->get_result();

$listings = [];
while ($row = $listingsResult->fetch_assoc()) {
    $listings[] = $row;
}

// ----------------------------
// Final JSON Response
// ----------------------------
echo json_encode([
    "success" => true,
    "account" => [
        "customer" => $customer,
        "cards" => $cards,
        "rocksOwned" => $rocksOwned,
        "listings" => $listings
    ]
]);

$conn->close();
?>
