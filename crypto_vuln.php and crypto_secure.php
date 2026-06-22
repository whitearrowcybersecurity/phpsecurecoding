---------------------------------
crypto_vuln.php
---------------------------------
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cc_number = $_POST['cc_number'];

    // VULNERABILITY 1: Hardcoded cryptographic key. 
    // Anyone with read access to the repository or git history can steal this key.
    $encryption_key = "CorporateSecretKey123!"; 

    // VULNERABILITY 2: Using AES-128-ECB mode.
    // ECB mode does not use an Initialization Vector (IV). Identical inputs will 
    // ALWAYS result in identical ciphertexts, exposing structural data patterns.
    $cipher_method = "aes-128-ecb"; 

    // Encrypting without an IV
    $encrypted_cc = openssl_encrypt($cc_number, $cipher_method, $encryption_key);

    echo "<div style='color:red; font-family:monospace;'>";
    echo "<h3>Data Stored in Database:</h3>";
    echo "Raw Ciphertext (Base64): " . $encrypted_cc;
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Secure Payment Portal (Vulnerable Encryption)</h2>
    <form method="POST">
        <label>Enter Credit Card Number:</label><br>
        <input type="text" name="cc_number" placeholder="4111222233334444" required><br><br>
        <input type="submit" value="Save Credit Card">
    </form>
</body>
</html>



---------------------------------
How it works
---------------------------------


est Case 1: The Cryptographic Pattern Leak (ECB Flaw)
Action: Open crypto_vuln.php and input a repeating block of text, such as 1111222211112222. 
Note down the resulting ciphertext string. Now input it again.

The Vulnerability: Because ECB encrypts data block-by-block without any randomizing initialization vector, 
the exact same pieces of data turn into the exact same cipher text blocks. An attacker monitoring encrypted records can figure out pattern repetitions 
(e.g., distinguishing distinct card issuers or guessing common values) purely by looking at structural similarities in the database without even knowing the key!

Test Case 2: Code Repository Leak (Hardcoded Key Flaw)
The Vulnerability: If you commit this file to your GitHub repository, the key CorporateSecretKey123! belongs to the world. 
If an attacker extracts the database later via an SQL injection or backup leak, they can instantly run openssl_decrypt() using your hardcoded string and decrypt your entire customer database in seconds.



---------------------------------
crypto_secure.php
---------------------------------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// SIMULATION: In a production environment, load this string out of an isolated 
// environment file (.env) located completely outside the public web directories.
// The key should ideally be a high-entropy 32-byte binary string.
define('SECURE_ENV_KEY', base64_decode('aU9zN3Y2eE9JM0pMZEpXU1ZreVVNdzA5bU5ZOWp0Y0U='));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cc_number = $_POST['cc_number'];
    
    // PATCH 1: Use AES-256-CBC which requires a unique, dynamic IV per transaction
    $cipher_method = "aes-256-cbc";
    
    // PATCH 2: Generate a cryptographically secure random IV 
    $iv_length = openssl_cipher_iv_length($cipher_method);
    $random_iv = openssl_random_pseudo_bytes($iv_length);
    
    // Encrypt using the strong key and unique IV
    $encrypted_raw = openssl_encrypt($cc_number, $cipher_method, SECURE_ENV_KEY, OPENSSL_RAW_DATA, $random_iv);
    
    // Because the database needs to store both the ciphertext and the IV to decrypt it later,
    // we safely prepend or bundle the IV alongside the encrypted payload.
    $final_payload = base64_encode($random_iv . $encrypted_raw);

    echo "<div style='color:green; font-family:monospace;'>";
    echo "<h3>Data Stored in Database (Secure):</h3>";
    echo "Randomized Ciphertext Payload: " . $final_payload;
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Secure Payment Portal (Secure Encryption)</h2>
    <form method="POST">
        <label>Enter Credit Card Number:</label><br>
        <input type="text" name="cc_number" placeholder="4111222233334444" required><br><br>
        <input type="submit" value="Save Credit Card">
    </form>
</body>
</html>
