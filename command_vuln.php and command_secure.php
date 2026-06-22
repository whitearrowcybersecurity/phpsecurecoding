-------------------------------------
command_vuln.php - vulnerable code
-------------------------------------

<?php
// VULNERABILITY: User input is passed directly to the system shell via shell_exec
if (isset($_POST['ip'])) {
    $target = $_POST['ip'];

    // The input is concatenated directly into the shell command string
    $cmd = "ping -c 3 " . $target; 
    
    // shell_exec executes the command in the OS environment and returns the output
    $output = shell_exec($cmd);

    echo "<pre>" . $output . "</pre>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Network Ping Utility (Vulnerable)</h2>
    <form method="POST">
        Enter IP Address: <input type="text" name="ip">
        <input type="submit" value="Ping">
    </form>
</body>
</html>


-------------------------------------
How the Attack Works

The script expects a simple IP address, such as 127.0.0.1. 
However, shell environments interpret certain characters (like ;, &&, and ||) as command separators, allowing multiple commands to run sequentially.

The Payload: 127.0.0.1; cat /etc/passwd (on Linux) or 127.0.0.1 & dir (on Windows).

The Resulting Command Executed by PHP:

ping -c 3 127.0.0.1; cat /etc/passwd

The Impact: 
The server will execute the ping command, finish it, and then immediately execute cat /etc/passwd, 
outputting the contents of the system's password file directly to the attacker's web browser. 
An attacker could use this to download application source code, plant web shells, or take full control of the server.
-------------------------------------

Note:
escapeshellarg() and escapeshellcmd() are different. 
escapeshellarg() treats the input as a single argument for a command, whereas escapeshellcmd() escapes characters that might allow running entirely different commands. 
When passing a single variable parameter, escapes


----------------
common_secure.php
-----------------
<?php
if (isset($_POST['ip'])) {
    $target = $_POST['ip'];

    // PATCH LAYER 1: Strict Input Validation (Allow-listing)
    // Validate that the input is strictly a well-formed IPv4 or IPv6 address.
    if (!filter_var($target, FILTER_VALIDATE_IP)) {
        die("Error: Invalid IP address format provided.");
    }

    // PATCH LAYER 2: Context-Specific Escaping
    // escapeshellarg() wraps the argument in single quotes and escapes existing quotes.
    // This prevents the shell from interpreting spaces or control characters as commands.
    $safe_target = escapeshellarg($target);

    // Build the command safely
    $cmd = "ping -c 3 " . $safe_target;
    
    // Execute the command safely
    $output = shell_exec($cmd);

    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Network Ping Utility (Secure)</h2>
    <form method="POST">
        Enter IP Address: <input type="text" name="ip">
        <input type="submit" value="Ping">
    </form>
</body>
</html>
