-----------------------------
mass_assignment_vuln.php
-----------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['user_id'] = 2; // Simulated logged-in standard user

$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // VULNERABILITY: Blindly building an update string out of the unvalidated $_POST array keys.
    // If an attacker appends fields like 'role', it gets injected directly into the query template.
    $update_fields = [];
    foreach ($_POST as $column => $value) {
        // Simple escaping prevents SQLi, but fails to prevent logic/access control manipulation
        $safe_value = $conn->real_escape_string($value);
        $update_fields[] = "`$column` = '$safe_value'";
    }
    
    $update_string = implode(', ', $update_fields);
    $query = "UPDATE accounts SET $update_string WHERE id = $user_id";
    
    if ($conn->query($query)) {
        echo "<p style='color:green; font-weight:bold;'>Profile updated successfully!</p>";
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}

// Fetch current info to populate the form
$result = $conn->query("SELECT * FROM accounts WHERE id = " . $_SESSION['user_id']);
$user_data = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Edit Profile (Vulnerable)</h2>
    <p>Your Current Registered System Role: <strong style="color:red;"><?php echo htmlspecialchars($user_data['role']); ?></strong></p>
    
    <form method="POST">
        <label>Update Email:</label><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>"><br><br>
        
        <label>Update Username:</label><br>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>"><br><br>
        
        <input type="submit" value="Save Profile Modifications">
    </form>
</body>
</html>




-----------------------------
How attack works:
-----------------------------
Test Case 1: Normal Authorized Use (The Baseline)
Goal: Verify standard user functionality.

Action: Open http://localhost/mass_assignment_vuln.php inside the browser. Modify the email field to new-dev@corporate.local and submit.

Expected UI Output: "Profile updated successfully!". The page reloads, showing the user's role remains unchanged as user.

Test Case 2: Exploitation via DevTools Parameter Injection (The Exploit)
Goal: Escalate privileges from user to admin by exploiting the dynamic binding loophole.

Action:

Right-click anywhere on the profile form and select Inspect Element (F12 DevTools).

Inside the HTML view, locate the <form> wrapper block.

Right-click the form layout inside DevTools, click Edit as HTML, and inject a hidden or text input field before the submit button:

HTML
<input type="text" name="role" value="admin">
Fill out the visible input boxes normally, and click the Save Profile Modifications button.

The Result: The browser includes role=admin within the structured body payload. The PHP script parses it, automatically building the command: UPDATE accounts SET email='...', username='...', role='admin' WHERE id = 2. 
The standard account successfully modifies its own administrative state.




--------------------------------------------
mass_assignment_secure.php
--------------------------------------------
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['user_id'] = 2; // Simulated standard user

$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // PATCH LAYER 1: Explicitly define an allowed array structure (Whitelisting keys)
    $allowed_updates = ['email', 'username'];
    $updates_to_apply = [];
    
    // Validate inputs individually or verify keys explicitly matches allowed definitions
    foreach ($allowed_updates as $field) {
        if (isset($_POST[$field])) {
            $updates_to_apply[$field] = $_POST[$field];
        }
    }
    
    if (!empty($updates_to_apply)) {
        // PATCH LAYER 2: Use an explicit Prepared Statement blueprint rather than dynamic variables
        $stmt = $conn->prepare("UPDATE accounts SET email = ?, username = ? WHERE id = ?");
        $stmt->bind_param("ssi", $updates_to_apply['email'], $updates_to_apply['username'], $user_id);
        
        if ($stmt->execute()) {
            echo "<p style='color:green; font-weight:bold;'>Profile securely updated!</p>";
        } else {
            echo "Execution error encountered.";
        }
        $stmt->close();
    }
}

// Fetch user data for display
$result = $conn->query("SELECT * FROM accounts WHERE id = " . $_SESSION['user_id']);
$user_data = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Edit Profile (Secure)</h2>
    <p>Your Current Registered System Role: <strong style="color:green;"><?php echo htmlspecialchars($user_data['role']); ?></strong></p>
    
    <form method="POST">
        <label>Update Email:</label><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>"><br><br>
        
        <label>Update Username:</label><br>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>"><br><br>
        
        <input type="submit" value="Save Profile Modifications">
    </form>
</body>
</html>

