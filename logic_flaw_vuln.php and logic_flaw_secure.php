---------------------------------
logic_flaw_vuln.php
---------------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// Simulate a user balance of $100.00
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 100.00;
}

$item_price = 50.00; // Price of a premium software license

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']); // Cast to integer to prevent injection

    // VULNERABILITY: The application checks if the user can afford the total,
    // but fails to check if the quantity is a positive number.
    $total_cost = $item_price * $quantity;

    if ($_SESSION['balance'] >= $total_cost) {
        $_SESSION['balance'] -= $total_cost;
        echo "<div style='color:red; font-weight:bold; border:2px solid red; padding:10px;'>";
        echo "Transaction Completed!<br>";
        echo "Purchased Quantity: " . $quantity . "<br>";
        echo "Total Cost Calculated: $" . $total_cost . "<br>";
        echo "Your New Account Balance: $" . $_SESSION['balance'] . "<br>";
        echo "</div>";
    } else {
        echo "<p style='color:orange;'>Insufficient funds for this purchase.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Corporate Software Store (Vulnerable Checkout)</h2>
    <p>Your current available balance: <strong>$<?php echo number_format($_SESSION['balance'], 2); ?></strong></p>
    <p>Item: Premium Enterprise License | Price: <strong>$50.00</strong></p>
    
    <form method="POST">
        <label>Enter Quantity to Purchase:</label>
        <input type="number" name="quantity" value="1" required>
        <input type="submit" value="Complete Checkout">
    </form>
    <br>
    <a href="logic_flaw_vuln.php?reset=1">Reset Balance</a>
    <?php if(isset($_GET['reset'])) { $_SESSION['balance'] = 100.00; header("Location: logic_flaw_vuln.php"); } ?>
</body>
</html>


------------------------------------
How the Attack Works & Test Cases
Test Case 1: Standard Purchasing Action
Action: Open logic_flaw_vuln.php. Enter 2 into the quantity box and submit.

Result: Total cost is calculated as $100.00. Your balance drops down to $0.00. The code behaves exactly as expected.

Test Case 2: The Negative Multiplier Exploit
Action: 1. Reset your balance using the reset link if needed.
2. In the quantity field, type a negative number, such as -5, and click Complete Checkout.

The Logic Breakdown: * The code processes the math: $total_cost = 50.00 * -5; which equals -$250.00.

The check evaluates: if ($_SESSION['balance'] >= -250.00). Since any positive balance is greater than a negative number, this evaluates to True.

The final balance calculation executes: $_SESSION['balance'] -= (-250.00);. In mathematics, subtracting a negative number adds it to the total.

The Impact: The transaction succeeds, and your account balance is artificially inflated to $350.00. The attacker has exploited the application's business rules to print money out of thin air.


------------------------------------
logic_flaw_secure.php
------------------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 100.00;
}

$item_price = 50.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);

    // PATCH: Validate business logic constraints
    // Enforce that quantity must be at least 1 and below a reasonable safety ceiling (e.g., 1000)
    if ($quantity <= 0 || $quantity > 1000) {
        header('HTTP/1.1 400 Bad Request');
        die("<p style='color:red; font-weight:bold;'>Error: Invalid item quantity requested.</p>");
    }

    $total_cost = $item_price * $quantity;

    if ($_SESSION['balance'] >= $total_cost) {
        $_SESSION['balance'] -= $total_cost;
        echo "<div style='color:green; font-weight:bold; border:2px solid green; padding:10px;'>";
        echo "Transaction Completed Safely!<br>";
        echo "Purchased Quantity: " . $quantity . "<br>";
        echo "Total Cost: $" . number_format($total_cost, 2) . "<br>";
        echo "Remaining Balance: $" . number_format($_SESSION['balance'], 2) . "<br>";
        echo "</div>";
    } else {
        echo "<p style='color:orange;'>Insufficient funds for this purchase.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Corporate Software Store (Secure Checkout)</h2>
    <p>Your current available balance: <strong>$<?php echo number_format($_SESSION['balance'], 2); ?></strong></p>
    <p>Item: Premium Enterprise License | Price: <strong>$50.00</strong></p>
    
    <form method="POST">
        <label>Enter Quantity to Purchase:</label>
        <input type="number" name="quantity" value="1" min="1" max="1000" required>
        <input type="submit" value="Complete Checkout">
    </form>
</body>
</html>
