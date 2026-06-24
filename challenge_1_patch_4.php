Vulnerable block
-----------------------------------
// Deletion Flaw
$del_query = "DELETE FROM support_tickets WHERE id = " . $ticket_id;
$conn->query($del_query);

// Data Leaking Fetch Flaw
$all_tickets = $conn->query("SELECT * FROM support_tickets")->fetch_all(MYSQLI_ASSOC);


-----------------------------------

// 1. SECURE DELETION: Use a prepared statement checking ownership context
$stmt = $conn->prepare("DELETE FROM support_tickets WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $ticket_id, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $msg = "Ticket item closed.";
} else {
    $msg = "Error: Access denied or record missing.";
}
$stmt->close();

// 2. SECURE DATA FETCHING: Limit the selection scope based on the active session user
$stmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$all_tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
