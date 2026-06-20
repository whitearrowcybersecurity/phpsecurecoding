<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// SIMULATION: A regular, low-privileged user logs in.
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'user'; // This user is NOT an administrator

// VULNERABILITY: The script verifies the user is logged in, 
// but entirely fails to verify if the user's role is 'admin'.
if (!isset($_SESSION['user_id'])) {
    die("Access Denied: Please log in first.");
}

// If the delete request is received, process it immediately
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    $conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>User ID " . htmlspecialchars($delete_id) . " successfully deleted!</p>";
    } else {
        echo "Error deleting user.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Administrative Action Panel (Vulnerable)</h2>
    <p>Current Role: <strong><?php echo $_SESSION['role']; ?></strong></p>
    
    <form method="POST">
        <label>Enter User ID to Delete:</label>
        <input type="text" name="delete_id">
        <input type="submit" value="Force Delete User">
    </form>
</body>
</html>




--------------------------------
How the Attack Works
Developers often believe that if they don't show the link to the admin panel on the regular user dashboard, normal users can't access it. This is "security through obscurity."

The Setup: 
A regular user with $_SESSION['role'] = 'user' navigates to the page URL directly: http://localhost/admin_bypass_vuln.php.

The Attack: 
Even though the UI says "Administrative Action Panel," the backend script checks isset($_SESSION['user_id']). Since the regular user is logged in, the check passes.

The Impact: 
The regular user enters a user ID (e.g., 1 for the administrator account) and submits the form. The system executes the deletion query. The regular user has successfully performed vertical privilege escalation, executing administrative actions without administrative rights.


--------------------------------
admin_bypass_secure.php
--------------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// SIMULATION: Regular user logs in.
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'user'; 

// PATCH LAYER 1: Authentication Check
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    die("Access Denied: Authentication required.");
}

// PATCH LAYER 2: Authorization Check (Role Verification)
// Explicitly check if the logged-in session role is permitted to use this functionality
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo "<h2>Access Denied</h2>";
    echo "<p style='color:red;'>Error 403: You do not have administrative privileges to perform this action.</p>";
    exit(); // Terminate execution immediately
}

// Proceed ONLY if the user is verified as an admin
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    $conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>User ID " . htmlspecialchars($delete_id) . " successfully deleted!</p>";
    } else {
        echo "Error deleting user.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Administrative Action Panel (Secure)</h2>
    <p>Current Role: <strong><?php echo $_SESSION['role']; ?></strong></p>
    
    <form method="POST">
        <label>Enter User ID to Delete:</label>
        <input type="text" name="delete_id">
        <input type="submit" value="Force Delete User">
    </form>
</body>
</html>
