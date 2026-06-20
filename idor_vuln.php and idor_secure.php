-------------------------
idor_vuln.php
-------------------------


<?php
// Enable explicit error reporting on the screen for debugging your training lab
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['user_id'] = 2; // Simulating logged-in user

// Change these values if your local MySQL user/password is different (e.g., "root" with no password "")
$db_host = "localhost";
$db_user = "db_user";   // Updated from root
$db_pass = "db_pass";   // Updated from empty string
$db_name = "secure_db";

try {
    // Establish connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Database Connection Failed: " . $conn->connect_error);
    }

    if (!isset($_GET['account_id'])) {
        die("Please provide an account_id in the URL. Example: ?account_id=2");
    }

    $account_id = $_GET['account_id']; 

    // VULNERABILITY: Directly fetching object without authorization checking
    $stmt = $conn->prepare("SELECT username, email, credit_card_number FROM accounts WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("SQL Preparation Failed: " . $conn->error);
    }

    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "<h2>Account Profile (Vulnerable File)</h2>";
        echo "<strong>Username:</strong> " . htmlspecialchars($row['username']) . "<br>";
        echo "<strong>Email:</strong> " . htmlspecialchars($row['email']) . "<br>";
        echo "<strong>Secret Data:</strong> " . htmlspecialchars($row['credit_card_number']) . "<br>";
    } else {
        echo "Account not found.";
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Beautifully intercepts fatal errors so you don't get a generic 500 page
    echo "<h3>Lab Configuration Error:</h3>";
    echo "<p style='color:red; font-family:monospace;'>" . $e->getMessage() . "</p>";
    echo "<p>Please ensure your MySQL server is running and the database schema has been imported.</p>";
}
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


-- 1. Create the database
CREATE DATABASE IF NOT EXISTS secure_db;
USE secure_db;

-- 2. Create the accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    credit_card_number VARCHAR(50) NOT NULL
);

-- 3. Insert mock data for testing (ID 1 is admin, ID 2 is our test user)
INSERT INTO accounts (id, username, email, credit_card_number) VALUES 
(1, 'admin_user', 'admin@corporate.local', '4111-2222-3333-4444'),
(2, 'regular_developer', 'dev@corporate.local', '5555-6666-7777-8888');






----------------------------------------------

idor_secure.php

----------------------------------------------

<?php
session_start();

// Simulate that user ID 2 is logged in
$_SESSION['user_id'] = 2; 

$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$account_id = $_GET['account_id'];
$logged_in_user = $_SESSION['user_id'];

// PATCH: Enforce authorization validation
// Verify that the requested account_id strictly matches the logged-in session user's ID
if ($account_id != $logged_in_user) {
    // Alternatively, you can run a query where user_id is hardcoded to the session value:
    // "SELECT ... FROM accounts WHERE id = ?" and bind $_SESSION['user_id'] directly.
    
    header('HTTP/1.1 403 Forbidden');
    die("Access Denied: You do not have permission to view this account profile.");
}

// Proceed only after authorization validation passes
$stmt = $conn->prepare("SELECT username, email, credit_card_number FROM accounts WHERE id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<h2>Account Profile (Secure)</h2>";
    echo "Username: " . htmlspecialchars($row['username']) . "<br>";
    echo "Email: " . htmlspecialchars($row['email']) . "<br>";
    echo "Secret Data: " . htmlspecialchars($row['credit_card_number']) . "<br>";
} else {
    echo "Account not found.";
}

$stmt->close();
$conn->close();
?>


