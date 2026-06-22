--------------------------
get_exposure_vuln.php
--------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['user_id'] = 2; // Simulated logged-in user

// VULNERABILITY: Processing highly sensitive credential updates out of the $_GET array
if (isset($_GET['new_password']) && isset($_GET['confirm_password'])) {
    $new_pass = $_GET['new_password'];
    $confirm_pass = $_GET['confirm_password'];

    if ($new_pass === $confirm_pass) {
        // In a real application, you would update the database here using password_hash()
        echo "<p style='color:red; font-weight:bold;'>Password successfully updated to: " . htmlspecialchars($new_pass) . "</p>";
        echo "<p>⚠️ Look closely at your browser's address bar right now!</p>";
    } else {
        echo "<p style='color:red;'>Passwords do not match.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Account Management — Update Password (Vulnerable)</h2>
    
    <form method="GET" action="get_exposure_vuln.php">
        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br><br>
        
        <label>Confirm New Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>
        
        <input type="submit" value="Change Password">
    </form>
</body>
</html>


-----------------------------
How the Attack Works & Test Cases
Test Case 1: The Visual Exposure (Browser History & Address Bar)
Action: Open get_exposure_vuln.php in your lab browser. Type MySuperSecret2026! into both password fields and click Change Password.

The Vulnerability: Look up at your browser's URL address bar. You will see the entire secret exposed in plain text:
http://localhost/get_exposure_vuln.php?new_password=MySuperSecret2026!&confirm_password=MySuperSecret2026!

The Impact: Even though the input field hid the characters as bullets (•••••) while typing, the GET mechanism leaks the data immediately. Anyone standing behind the user can see the password. Furthermore, the password is now permanently saved inside the browser's local History logs.

Test Case 2: Infrastructure Leakage (The Enterprise Risk)
The Behind-the-Scenes Exploit: When an HTTP GET request passes through a corporate network or the internet, the entire URL string (including query parameters) is explicitly captured by:

The local corporate network proxy/reverse proxy logs.

The web server's access log file (e.g., Apache's access.log or Nginx's error.log).

Third-party analytics scripts if the page includes external resources (leaked via the HTTP Referer header).

The Consequence: An attacker who compromises a secondary infrastructure monitor, logging aggregator, or server access log file gains immediate access to corporate user passwords in clear text, without ever needing to touch the primary application database.




---------------------------------
get_exposure_secure.php
---------------------------------
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['user_id'] = 2;

// PATCH LAYER 1: Extract sensitive credential values strictly out of the $_POST array
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass === $confirm_pass) {
            // Apply secure hashing pattern established in Vulnerability #11
            $secure_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // PATCH LAYER 2: Post-Redirect-Get (PRG) Pattern
            // Redirect immediately to prevent data from being re-submitted on page refresh
            $_SESSION['success_msg'] = "Password securely updated!";
            header("Location: get_exposure_secure.php");
            exit();
        } else {
            echo "<p style='color:red;'>Passwords do not match.</p>";
        }
    }
}

// Display success message safely if it exists in the session state
if (isset($_SESSION['success_msg'])) {
    echo "<p style='color:green; font-weight:bold;'>" . htmlspecialchars($_SESSION['success_msg']) . "</p>";
    unset($_SESSION['success_msg']); // Clear single-use flash message
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Account Management — Update Password (Secure)</h2>
    
    <form method="POST" action="get_exposure_secure.php">
        <label>New Password:</label><br>
        <input type="password" name="new_password" autocomplete="off" required><br><br>
        
        <label>Confirm New Password:</label><br>
        <input type="password" name="confirm_password" autocomplete="off" required><br><br>
        
        <input type="submit" value="Change Password">
    </form>
</body>
</html>


-------------------------------
GET requests must be idempotent, meaning they should only be used to read or fetch data, never to create, modify, delete, or authenticate states. Any action that alters data or processes secrets must use POST, PUT, or DELETE requests.
