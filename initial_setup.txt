sudo apt update
sudo apt upgrade -y

sudo apt install apache2 php php-mysql mariadb-server -y

sudo systemctl start apache2
sudo systemctl start mariadb

sudo systemctl enable apache2
sudo systemctl enable mariadb

php -v
mysql --version


------------------------------
Secure and Configure MariaDB
------------------------------
sudo mysql -u root -p
password:root

CREATE DATABASE secure_db;
USE secure_db;


----------------------------------
CREATE USER 'db_user'@'localhost'
IDENTIFIED BY 'db_pass';

GRANT ALL PRIVILEGES ON secure_db.*
TO 'db_user'@'localhost';

FLUSH PRIVILEGES;

----------------------------------


USE secure_db;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    email VARCHAR(100)
);

-----------------------------------

SHOW TABLES;

-----------------------------------
INSERT INTO users (username,email)
VALUES
('admin','admin@example.com'),
('john','john@example.com'),
('alice','alice@example.com'),
('bob','bob@example.com');

-----------------------------------


SELECT * FROM users;


-----------------------------------

exit;

----------------------------------------------------------------------
sudo nano /var/www/html/sqli_vuln.php

----------------------------------------------------------------------
----------------------------------------------------------------------
<?php
$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];

$query = "SELECT username, email FROM users WHERE id = " . $id;

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "User: " . $row['username'] .
             " - Email: " . $row['email'] . "<br>";
    }
} else {
    echo "No user found.";
}

$conn->close();
?>

----------------------------------------------------------------------
----------------------------------------------------------------------


