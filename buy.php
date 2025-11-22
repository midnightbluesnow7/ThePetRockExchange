<?php
session_start();
require "php/db.php";

// Redirect if not logged in
if (!isset($_SESSION['CustID'])) {
    header("Location: index.php");
    exit;
}

// Get rockID from query
$rockID = $_GET['rockID'] ?? null;

if (!$rockID) {
    die("No rock selected.");
}

// Fetch rock info from DB
$stmt = $conn->prepare("SELECT Name, Price, RockDes, RockRarity, PNG_JPG FROM rockinfo WHERE RockID=?");
$stmt->bind_param("i", $rockID);
$stmt->execute();
$result = $stmt->get_result();
$rock = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$rock) {
    die("Rock not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buy <?php echo htmlspecialchars($rock['Name']); ?> | The Pet Rock Exchange</title>
<link rel="stylesheet" href="main.css">
<link rel="stylesheet" href="buy.css">
</head>
<body>
<!-- Navigation -->
<nav>
    <div class="nav-container">
        <h1>The Pet Rock Exchange</h1>
        <ul>
            <li><a href="main.php">Buy</a></li>
            <li><a href="sell.php">Sell</a></li>
            <li><a href="trade.php">Trade</a></li>
            <li><a href="account.php">Account</a></li>
            <li><a href="#">About</a></li>
        </ul>
    </div>
</nav>

<main>
    <div class="container purchase-container">
        <h2 class="section-title">Confirm Your Pet Rock Purchase</h2>
        <div class="purchase-content">
            <div class="rock-preview">
                <img src="assets/images/<?php echo htmlspecialchars($rock['PNG_JPG']); ?>" alt="<?php echo htmlspecialchars($rock['Name']); ?>">
                <div class="price-info">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($rock['Name']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($rock['RockDes']); ?></p>
                    <p><strong>Rarity:</strong> <?php echo htmlspecialchars($rock['RockRarity']); ?></p>
                    <p><strong>Price:</strong> 💎 <span id="rockPrice"><?php echo $rock['Price']; ?></span></p>
                </div>
            </div>

            <div class="payment-form">
                <h3>Payment Details</h3>
                <form id="purchaseForm">
                    <label for="cardName">Name on Card</label>
                    <input type="text" id="cardName" placeholder="Alex Stonekeeper" required>
                    <label for="cardNumber">Card Number</label>
                    <input type="text" id="cardNumber" maxlength="19" placeholder="1234 5678 9012 3456" required>
                    <label for="expiration">Expiration Date</label>
                    <input type="month" id="expiration" required>
                    <label for="ccv">CCV</label>
                    <input type="text" id="ccv" maxlength="4" placeholder="123" required>
                    <label for="billingAddress">Billing Address</label>
                    <input type="text" id="billingAddress" placeholder="123 Rock Lane, Boulder City, CO" required>
                    <button type="submit" class="confirm-btn">Confirm Purchase</button>
                </form>
            </div>
        </div>
    </div>
</main>

<footer>
&copy; 2025 The Pet Rock Exchange. All rights reserved.
</footer>

<script>
const accountBalanceEl = document.getElementById("accountBalance");
const rockPriceEl = document.getElementById("rockPrice");
const newBalanceEl = document.getElementById("newBalance");
const form = document.getElementById("purchaseForm");

form.addEventListener("submit", (e) => {
    e.preventDefault();
    const current = parseFloat(accountBalanceEl.textContent);
    const price = parseFloat(rockPriceEl.textContent);
    const updated = (current - price).toFixed(2);
    newBalanceEl.textContent = updated;

    alert(`🎉 You have purchased "<?php echo htmlspecialchars($rock['Name']); ?>" for 💎${price}!`);
});
</script>
</body>
</html>
