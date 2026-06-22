---------------------------------------
redirect_vuln.php
---------------------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// VULNERABILITY: Accepting an absolute URL directly from user input
// and blindly passing it into the Location header redirect system.
if (isset($_GET['next'])) {
    $redirect_url = $_GET['next'];
    
    // The application forces the browser to navigate to the exact string supplied
    header("Location: " . $redirect_url);
    exit();
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Login Redirect Gateway (Vulnerable)</h2>
    <p>Simulating a successful authentication process...</p>
    
    <a href="redirect_vuln.php?next=dashboard.php">Continue to Your Dashboard Account</a>
</body>
</html>





---------------------------------------
How the Attack Works
---------------------------------------
Test Case 1: Legitimate Internal Behavior
Action: Navigate to http://localhost/redirect_vuln.php?next=dashboard.php

Result: The system accurately redirects you to http://localhost/dashboard.php. This is the intended functional outcome.

Test Case 2: Phishing Exploitation (The Exploit)
Action: An attacker crafts a malicious URL link and sends it to a corporate victim via an email or messaging app:
http://localhost/redirect_vuln.php?next=https://www.google.com (In a real scenario, they would point it to a cloned phishing site like https://your-company-login.attacker.local)

The Result: The victim looks closely at the link before clicking. Because it starts with your trusted server host (http://localhost/), they feel completely safe clicking it. However, upon arrival, the PHP script extracts https://www.google.com and hands it to the browser.
The browser instantly moves out of your server environment and lands on the attacker's target page.




---------------------------------------
redirect_secure.php
---------------------------------------
