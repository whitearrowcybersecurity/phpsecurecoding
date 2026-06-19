-------------------------
Vulnerable code
-------------------------


<?php
// Database connection (Assume it works for this example)
$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// VULNERABILITY: Directly concatenating user input into the SQL string
$id = $_GET['id']; 
$query = "SELECT username, email FROM users WHERE id = " . $id;

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "User: " . $row['username'] . " - Email: " . $row['email'] . "<br>";
    }
} else {
    echo "No user found.";
}

$conn->close();
?>





-------------------------
Secure code
-------------------------

<?php
$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PATCH: Use a prepared statement with a placeholder (?)
$id = $_GET['id'];

// 1. Prepare the SQL template
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");

if ($stmt) {
    // 2. Bind the parameters ('i' stands for integer)
    // This safely escapes the input and guarantees it is treated as an integer
    $stmt->bind_param("i", $id);
    
    // 3. Execute the query
    $stmt->execute();
    
    // 4. Get the result set
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "User: " . htmlspecialchars($row['username']) . " - Email: " . htmlspecialchars($row['email']) . "<br>";
        }
    } else {
        echo "No user found.";
    }
    
    $stmt->close();
}

$conn->close();
?>


-----------------------------
test cases:

Input	                    Result
?id=1	                    Accepted
?id=123	                    Accepted
?id=1 OR 1=1	            Invalid ID
?id=' OR '1'='1	            Invalid ID
?id=abc	                    Invalid ID
-----------------------------
