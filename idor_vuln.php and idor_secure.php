-------------------------
idor_vuln.php
-------------------------


<?php
session_start();

// Assume the user is logged in. In a real app, session verification happens here.
// For testing, let's simulate that user ID 2 is logged in.
$_SESSION['user_id'] = 2; 

$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

// VULNERABILITY: The app trusts the 'account_id' from the URL query string
// without checking if it belongs to the currently logged-in session user.
$account_id = $_GET['account_id']; 

$stmt = $conn->prepare("SELECT username, email, credit_card_number FROM accounts WHERE id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<h2>Account Profile</h2>";
    echo "Username: " . htmlspecialchars($row['username']) . "<br>";
    echo "Email: " . htmlspecialchars($row['email']) . "<br>";
    echo "Secret Data: " . htmlspecialchars($row['credit_card_number']) . "<br>";
} else {
    echo "Account not found.";
}

$stmt->close();
$conn->close();
?>





----------------------------------
How the Attack Works
The application accurately uses a prepared statement, so it is safe from SQL injection. 
However, it completely lacks authorization checks.

The Setup: 
A legitimate user (User ID 2) logs in and is directed to their profile URL: idor_vuln.php?account_id=2.

The Attack: 
The user changes the URL parameter manually in their browser to look at a different ID: idor_vuln.php?account_id=1.

The Impact: 
Because the script only checks if the account exists rather than who owns it, 
User 2 can now view the private username, email, and credit card information of User 1 (the administrator or another customer).

----------------------------------




