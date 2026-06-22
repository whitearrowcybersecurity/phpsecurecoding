------------------------
hash_vuln.php
------------------------
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // VULNERABILITY: Using MD5 to hash the password.
    // MD5 is extremely fast and lacks a unique salt per user, making it completely insecure.
    $weak_hash = md5($password);

    $stmt = $conn->prepare("INSERT INTO accounts (username, email, credit_card_number, role) VALUES (?, ?, ?, ?)");
    // For this lab demo, we inject the weak hash into the email column to see it easily
    $stmt->bind_param("ssss", $username, $weak_hash, $password, $password);
    
    if ($stmt->execute()) {
        echo "<p style='color:red;'>User registered! MD5 Hash stored: <strong>" . $weak_hash . "</strong></p>";
    } else {
        echo "Registration error.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>User Registration Portal (Vulnerable Hashing)</h2>
    <form method="POST">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <input type="submit" value="Register Account">
    </form>
</body>
</html>




------------------------
How it works
------------------------

Test Case 1: The Rainbow Table Lookup (The Exploit)
Action: Open hash_vuln.php and register an account with a common corporate password, like Password123.

The Result: The script outputs the resulting MD5 hash: 42f74da81c90538a7191f6305a2f5207.

The Attack: Imagine an attacker compromises the database and steals this hash. Instead of spending days brute-forcing it, they copy 42f74da81c90538a7191f6305a2f5207 and paste it into a public online lookup tool (like CrackStation).

The Impact: Because MD5 is entirely deterministic (the word Password123 always produces the exact same hash), lookup engines instantly map the hash back to the plaintext password in milliseconds.


------------------------
hash_secure.php
------------------------


<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // PATCH: Utilize PHP's built-in password_hash system
    // PASSWORD_DEFAULT dynamically adapts to the strongest current algorithm (Bcrypt/Argon2id)
    // This automatically handles generating a strong, secure cryptographical salt per user.
    $secure_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO accounts (username, email, credit_card_number, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $secure_hash, $password, $password);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>User registered safely!</p>";
        echo "<p>Secure Hash stored: <span style='font-family:monospace; font-size:12px;'>" . $secure_hash . "</span></p>";
    } else {
        echo "Registration error.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>User Registration Portal (Secure Hashing)</h2>
    <form method="POST">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <input type="submit" value="Register Account">
    </form>
</body>
</html>
