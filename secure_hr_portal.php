sudo mysql -u db_user -pdb_pass -e "USE secure_db; ALTER TABLE accounts ADD COLUMN role VARCHAR(50) DEFAULT 'user';"


-----------
secure_hr_portal.php
-----------

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// -------------------------------------------------------------------------
// LAB SETUP & STATE SIMULATION
// -------------------------------------------------------------------------
// Simulate that Employee ID 2 ("regular_developer") has authenticated.
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2;
    $_SESSION['username'] = 'dev_team';
    $_SESSION['role'] = 'Staff Engineer';
}

// Establish database connection using working lab user credentials
$conn = new mysqli("localhost", "db_user", "db_pass", "secure_db");
if ($conn->connect_error) {
    die("<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>Database Connection Failed: " . htmlspecialchars($conn->connect_error) . "</div>");
}

// Ensure the database state matches our demo criteria dynamically
$conn->query("CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    email VARCHAR(100),
    credit_card_number VARCHAR(50),
    role VARCHAR(50) DEFAULT 'user'
)");

// Seed initial values safely if table is currently blank
$check_empty = $conn->query("SELECT id FROM accounts LIMIT 1");
if ($check_empty->num_rows === 0) {
    $conn->query("INSERT INTO accounts (id, username, email, credit_card_number, role) VALUES 
    (1, 'administrator', 'admin@securehr.local', '4111-2222-3333-4444', 'admin'),
    (2, 'regular_developer', 'dev@securehr.local', '5555-6666-7777-8888', 'user')");
}

// Initialize notification alerts
$alert_message = '';
$alert_type = '';

// -------------------------------------------------------------------------
// CONTROLLER LOGIC (SECURE IMPLEMENTATIONS)
// -------------------------------------------------------------------------

// Feature A: Secure Profile Update (Defending against IDOR & Mass Assignment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    // 1. IDOR DEFENSE: We completely ignore external ID input. The user identifier
    // is pulled strictly from the immutable, protected server-side session variable.
    $authenticated_session_id = $_SESSION['user_id'];
    
    // 2. MASS ASSIGNMENT DEFENSE: Explicit parameter mapping (Allow-list architecture)
    $allowed_payload = ['email', 'username'];
    $validated_data = [];
    
    foreach ($allowed_payload as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $validated_data[$field] = trim($_POST[$field]);
        }
    }
    
    if (!empty($validated_data['email']) && !empty($validated_data['username'])) {
        // 3. INJECTION DEFENSE: Bind data parameters using a secure prepared statement template
        $stmt = $conn->prepare("UPDATE accounts SET email = ?, username = ? WHERE id = ?");
        $stmt->bind_param("ssi", $validated_data['email'], $validated_data['username'], $authenticated_session_id);
        
        if ($stmt->execute()) {
            $alert_message = "Profile configurations updated successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "An error occurred during profile processing.";
            $alert_type = "error";
        }
        $stmt->close();
    }
}

// Feature B: Secure API Token Generation (Defending against Weak Randomness)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_generate_token'])) {
    try {
        // CSPRNG DEFENSE: Pull high-entropy binary strings directly from OS kernel space
        $crypto_bytes = random_bytes(32);
        $_SESSION['generated_api_token'] = bin2hex($crypto_bytes);
        $alert_message = "New enterprise API integration token issued successfully.";
        $alert_type = "success";
    } catch (Exception $e) {
        $alert_message = "Cryptographic system failure: Insufficient environment entropy.";
        $alert_type = "error";
    }
}

// Fetch current user details securely to display on the layout
$user_stmt = $conn->prepare("SELECT username, email, role FROM accounts WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$current_user_profile = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Feature C: Directory Search Functionality (Defending against SQLi & Reflected XSS)
$search_results = [];
$raw_search_query = '';

if (isset($_GET['search_query']) && trim($_GET['search_query']) !== '') {
    $raw_search_query = $_GET['search_query'];
    
    // SQL INJECTION DEFENSE: Avoid dynamic concatenation; bind the search variable
    $search_term = "%" . $raw_search_query . "%";
    $search_stmt = $conn->prepare("SELECT username, email, role FROM accounts WHERE username LIKE ? OR role LIKE ?");
    $search_stmt->bind_param("ss", $search_term, $search_term);
    $search_stmt->execute();
    $search_results = $search_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $search_stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureHR Enterprise Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans">

    <header class="bg-indigo-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-emerald-500 text-indigo-950 font-black p-2 rounded-lg text-xs tracking-wider">SECURE VER.</div>
                <h1 class="text-xl font-bold tracking-tight">SecureHR Portal</h1>
            </div>
            <div class="text-xs text-indigo-200 bg-indigo-950/60 px-3 py-1.5 rounded-md border border-indigo-700/50">
                Active Session Identity: <span class="text-emerald-400 font-mono font-semibold"><?php echo htmlspecialchars($current_user_profile['username'], ENT_QUOTES, 'UTF-8'); ?></span> 
                (<?php echo htmlspecialchars($current_user_profile['role'], ENT_QUOTES, 'UTF-8'); ?>)
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 py-8">
        
        <?php if (!empty($alert_message)): ?>
            <div class="mb-6 p-4 rounded-xl border flex items-center gap-3 <?php echo $alert_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'; ?>">
                <span class="text-lg"><?php echo $alert_type === 'success' ? '✅' : '⚠️'; ?></span>
                <p class="font-medium text-sm"><?php echo htmlspecialchars($alert_message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <section class="lg:col-span-1 space-y-8">
                
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        👤 Account Profile Information
                    </h2>
                    
                    <form method="POST" action="secure_portal.php" class="space-y-4">
                        <input type="hidden" name="action_update_profile" value="1">
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Username Handle</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($current_user_profile['username'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 transition-colors" required>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Corporate Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($current_user_profile['email'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 transition-colors" required>
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm py-2 px-4 rounded-lg transition-colors cursor-pointer shadow-sm shadow-indigo-100">
                            Apply Changes Safely
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        🔑 Integration API Credentials
                    </h2>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        Generate authentication credentials to interact with internal API infrastructure. These values are computed instantly using random operating system entropy tokens.
                    </p>

                    <form method="POST" action="secure_portal.php" class="mb-4">
                        <input type="hidden" name="action_generate_token" value="1">
                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm py-2 px-4 rounded-lg transition-colors cursor-pointer shadow-sm">
                            Issue Cryptographic Token
                        </button>
                    </form>

                    <?php if (isset($_SESSION['generated_api_token'])): ?>
                        <div class="p-3 bg-slate-900 text-emerald-400 rounded-xl border border-slate-800">
                            <span class="block text-[10px] uppercase font-bold tracking-widest text-slate-400 mb-1">Generated Secret Key</span>
                            <code class="text-xs break-all font-mono font-bold select-all"><?php echo htmlspecialchars($_SESSION['generated_api_token'], ENT_QUOTES, 'UTF-8'); ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        🔍 Corporate Directory Lookup Engine
                    </h2>
                    
                    <form method="GET" action="secure_portal.php" class="flex gap-2 mb-6">
                        <input type="text" name="search_query" value="<?php echo htmlspecialchars($raw_search_query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search colleague names or role categories..." class="flex-grow bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 font-medium text-sm rounded-lg transition-colors cursor-pointer shadow-sm shadow-indigo-100">
                            Search
                        </button>
                    </form>

                    <?php if ($raw_search_query !== ''): ?>
                        <p class="text-xs text-slate-500 mb-4">
                            Showing parsed directory results for search term: <strong class="text-slate-900 font-semibold">"<?php echo htmlspecialchars($raw_search_query, ENT_QUOTES, 'UTF-8'); ?>"</strong>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($search_results)): ?>
                        <div class="overflow-hidden border border-slate-100 rounded-xl">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 font-medium text-xs uppercase tracking-wider">
                                        <th class="px-4 py-3">Employee Name</th>
                                        <th class="px-4 py-3">Email Association</th>
                                        <th class="px-4 py-3">Designated Role</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($search_results as $worker): ?>
                                        <tr class="hover:bg-slate-50/40 transition-colors">
                                            <td class="px-4 py-3.5 font-semibold text-slate-900"><?php echo htmlspecialchars($worker['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="px-4 py-3.5 font-mono text-xs text-slate-600"><?php echo htmlspecialchars($worker['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="px-4 py-3.5 text-slate-600"><span class="inline-block bg-slate-100 px-2 py-0.5 rounded-md font-medium text-xs text-slate-700 border border-slate-200/50"><?php echo htmlspecialchars($worker['role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($raw_search_query !== ''): ?>
                        <div class="text-center py-8 text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                            No directory matches located for that input.
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                            Enter an employee filter value above to execute database discovery rules.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-6 mt-12 text-center text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 space-y-2">
            <p class="font-semibold text-slate-500">🛡️ Implemented OWASP Proactive Security Control Layouts</p>
            <p>Context Encoding (XSS Defended) • Parametric Prepared Binding (SQLi Defended) • Canonical Role Whitelists (Mass Assignment Defended) • CSPRNG OS Kernel Streams (Predictability Defended)</p>
        </div>
    </footer>

</body>
</html>
