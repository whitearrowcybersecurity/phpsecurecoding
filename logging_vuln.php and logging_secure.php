-------------------------------
logging_vuln.php
-------------------------------
<?php
ini_set('display_errors', 0); // Production setting: errors are hidden from the UI

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Simulate database authentication check (Assume it fails for this demonstration)
    $login_successful = false; 

    if (!$login_successful) {
        // VULNERABILITY: Blindly logging the entire input array into a log file.
        // This writes the victim's cleartext password straight into the file system.
        $log_message = sprintf(
            "[%s] Failed login attempt for User: %s with Password: %s\n",
            date('Y-m-d H:i:s'),
            $username,
            $password
        );
        
        // Appends the data to a local file
        file_put_contents('app_debug.log', $log_message, FILE_APPEND);
        
        echo "<p style='color:red;'>Invalid credentials provided.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Corporate Portal Login (Vulnerable Logging)</h2>
    <form method="POST">
        <label>Username/Email:</label><br>
        <input type="text" name="username" required><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <input type="submit" value="Sign In">
    </form>
</body>
</html>



-------------------------------
How the Attack Works
-------------------------------
Test Case 1: The Accidental Exposure
Action: Open logging_vuln.php in your browser. Attempt to log in using the username admin@corporate.local and the password SuperSecretPassword2026!.

The Behind-the-Scenes Action: The authentication fails as expected. However, look inside your local directory. A new file named app_debug.log has been generated.

The Output inside app_debug.log:

Plaintext
[2026-06-22 14:30:15] Failed login attempt for User: admin@corporate.local with Password: SuperSecretPassword2026!
The Exploit Scenario
The Attack: The application itself might be heavily guarded against SQL Injection or XSS. However, weeks later, an attacker finds a minor Path Traversal leak (like Vulnerability #5) or a backup exposure on the server. Instead of targeting the database, they directly read app_debug.log.

The Impact: The attacker extracts hundreds of plain-text corporate passwords belonging to valid employees who simply mistyped their usernames during login, resulting in widespread credential harvesting.




-------------------------------
logging_secure.php
-------------------------------

<?php
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // This must NEVER go into a log file

    $login_successful = false; 

    if (!$login_successful) {
        // PATCH LAYER 1: Explicitly log ONLY non-sensitive tracking markers
        // We capture the username/IP but discard or completely mask the password field.
        $masked_password = "********"; 

        $log_message = sprintf(
            "[%s] Security Alert: Failed login attempt for Account: %s | Source IP: %s\n",
            date('Y-m-d H:i:s'),
            filter_var($username, FILTER_SANITIZE_EMAIL), // Sanitize strings before logging
            $_SERVER['REMOTE_ADDR']
        );
        
        // PATCH LAYER 2: Store logs in a secure folder outside the public web root directory
        // For this lab container display, we write to a safe designated file name
        file_put_contents('app_production.log', $log_message, FILE_APPEND);
        
        echo "<p style='color:red;'>Invalid credentials provided.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Corporate Portal Login (Secure Logging)</h2>
    <form method="POST">
        <label>Username/Email:</label><br>
        <input type="text" name="username" required><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <input type="submit" value="Sign In">
    </form>
</body>
</html>
