$title = trim($_POST['title'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($title !== '' && $desc !== '') {
    // Use prepared statement placeholders (?)
    $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, title, description) VALUES (?, ?, ?)");
    
    // Bind parameters safely ('iss' = integer, string, string)
    $stmt->bind_param("iss", $_SESSION['user_id'], $title, $desc);
    
    if ($stmt->execute()) {
        $msg = "Ticket successfully dispatched securely!";
    } else {
        $msg = "An error occurred during ticket processing.";
    }
    $stmt->close();
}
