<?php
// =========================================================================
// ENTERPRISE BUG HUNT CHALLENGE — LEVERAGE MANUAL REVIEW SKILLS
// WARNING: THIS FILE CONTAINS 5 DISTINCT SECURITY OR LOGICAL FLAWS.
// =========================================================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Simulated Session State: User ID 2 ("regular_developer") is logged in.
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2;
    $_SESSION['username'] = 'regular_developer';
    $_SESSION['role'] = 'user'; 
}

$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");

// Dynamically establish the required challenge table space
$conn->query("CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100),
    description TEXT
)");

// Seed sample records if the table is empty
$check = $conn->query("SELECT id FROM support_tickets LIMIT 1");
if ($check->num_rows === 0) {
    $conn->query("INSERT INTO support_tickets (id, user_id, title, description) VALUES 
    (501, 1, 'Admin Payroll Server Glitch', 'CRITICAL: The payroll root root password is set to CorporateAdmin2026.'),
    (502, 2, 'VPN Connection Dropping', 'My local Cisco VPN software keeps dropping sessions when compiling PHP code.')");
}

$msg = "";

// PROCESSING ROUTINE 1: Create a New Support Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];

    // Inserting values directly into the database
    $query = "INSERT INTO support_tickets (user_id, title, description) VALUES (" . $_SESSION['user_id'] . ", '$title', '$desc')";
    
    if ($conn->query($query)) {
        $msg = "Ticket successfully dispatched!";
    } else {
        $msg = "Database processing error: " . $conn->error;
    }
}

// PROCESSING ROUTINE 2: Close/Delete a Support Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    $ticket_id = intval($_POST['ticket_id']);

    // Processing the deletion transaction
    $del_query = "DELETE FROM support_tickets WHERE id = " . $ticket_id;
    if ($conn->query($del_query)) {
        $msg = "Ticket index item permanently closed.";
    }
}

// PROCESSING ROUTINE 3: Administrative Status Validation Check
// The developer intended to print a special notice only if the user is an admin.
$admin_status_notice = false;
if ($_SESSION['role'] = 'admin') { 
    $admin_status_notice = true;
}

// Gather all tickets to render out onto the employee view dashboard
$all_tickets = $conn->query("SELECT * FROM support_tickets")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internal IT Support Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 p-6 font-sans">

    <div class="max-w-4xl mx-auto space-y-6">
        
        <header class="bg-blue-900 text-white p-4 rounded-xl flex justify-between items-center">
            <h1 class="text-xl font-bold">IT Support Desk Alpha</h1>
            <span class="text-xs font-mono bg-blue-950 p-2 rounded">Session User: <?php echo $_SESSION['username']; ?></span>
        </header>

        <?php if ($msg !== ""): ?>
            <div class="p-3 bg-yellow-100 text-yellow-800 border border-yellow-200 rounded-lg text-sm"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if ($admin_status_notice === true): ?>
            <div class="p-3 bg-red-100 text-red-800 border border-red-200 rounded-lg text-xs font-bold">
                ⚠️ SYSTEM MAINTENANCE LOGS ACTIVE: Root database sync execution scheduled tonight.
            </div>
        <?php endif; ?>

        <div class="bg-white p-5 rounded-xl shadow-xs border border-gray-200">
            <h2 class="font-bold text-gray-800 mb-3">🎫 Submit Dynamic Support Request</h2>
            <form method="POST" action="ticket_challenge.php" class="space-y-3">
                <input type="hidden" name="action_create" value="1">
                <div>
                    <label class="block text-xs text-gray-500 font-bold uppercase mb-1">Issue Headline</label>
                    <input type="text" name="title" class="w-full border border-gray-300 rounded p-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 font-bold uppercase mb-1">Detailed Description</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded p-2 text-sm" required></textarea>
                </div>
                <button type="submit" class="bg-blue-600 text-white text-sm px-4 py-2 rounded font-bold hover:bg-blue-700">Submit Ticket</button>
            </form>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-xs border border-gray-200">
            <h2 class="font-bold text-gray-800 mb-4">📋 Global System Service Logs</h2>
            <div class="space-y-4">
                <?php foreach ($all_tickets as $ticket): ?>
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 relative">
                        <h3 class="font-bold text-gray-900 text-sm"><?php echo $ticket['title']; ?></h3>
                        <p class="text-xs text-gray-600 mt-1"><?php echo $ticket['description']; ?></p>
                        <span class="block text-[10px] text-gray-400 mt-2 font-mono">Assigned Owner Context ID: <?php echo $ticket['user_id']; ?></span>
                        
                        <form method="POST" action="ticket_challenge.php" class="absolute top-4 right-4">
                            <input type="hidden" name="action_delete" value="1">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <button type="submit" class="text-xs bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 rounded border border-red-200 transition-colors">Close Ticket</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</body>
</html>
