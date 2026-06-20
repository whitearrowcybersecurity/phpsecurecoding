---------------------------------
idor_vuln_2.php
---------------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// SIMULATION: Assume a standard user logs in, and the application assigns them User ID 2.
$_SESSION['user_id'] = 2; 

// Database configuration using your new working lab user credentials
$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// VULNERABILITY: The script accepts an arbitrary account_id from the URL query parameters
// and completely trusts that the user is allowed to access it.
if (!isset($_GET['account_id'])) {
    die("Please append an account ID to the URL. Example: ?account_id=2");
}
$account_id = $_GET['account_id']; 

// The query runs securely against SQL injection, but lacks an ownership verification step
$stmt = $conn->prepare("SELECT username, email, credit_card_number FROM accounts WHERE id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<h2>Account Profile Dashboard (Vulnerable File)</h2>";
    echo "<strong>Username:</strong> " . htmlspecialchars($row['username']) . "<br>";
    echo "<strong>Email:</strong> " . htmlspecialchars($row['email']) . "<br>";
    echo "<strong>Secret Data (CC):</strong> " . htmlspecialchars($row['credit_card_number']) . "<br>";
} else {
    echo "Account record not found.";
}

$stmt->close();
$conn->close();
?>

---------------------------------------------------------------------------------------------------

How the Attack Works
Navigate to the page as the normal user: http://localhost/idor_vuln.php?account_id=2. The application correctly shows the information for regular_developer.

An attacker notices that changing the account_id integer shifts the record being requested.

The attacker modifies the URL parameters directly within the browser address bar to target the administrator account: http://localhost/idor_vuln.php?account_id=1.

The Impact: Because the backend script checks whether the target record exists, but fails to verify who owns it, the attacker completely bypasses horizontal access barriers and displays sensitive details (like credit card numbers and emails) belonging to any user in the system.



-------------------------------------------------------
IDOR issues are rarely caught by automated static analysis tools (SAST) because the syntax itself (SELECT ... WHERE id = ?) is syntactically correct and safe from injection. 
IDOR is a logical flaw that can only be resolved by writing explicit, programmatic access rules or utilizing an Access Control Matrix.
