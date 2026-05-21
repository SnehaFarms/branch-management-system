<?php
// Include database connection
require_once 'config.php';

// Security Check: Only allow logged-in Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = "";
$error = "";

// Handle Branch Creation Form Submission
if (isset($_POST['create_branch'])) {
    $branch_code = trim($_POST['branch_code']);
    $branch_name = trim($_POST['branch_name']);

    if (!empty($branch_code) && !empty($branch_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO branches (branch_code, branch_name) VALUES (:code, :name)");
            $stmt->bindParam(':code', $branch_code);
            $stmt->bindParam(':name', $branch_name);
            $stmt->execute();
            $message = "Branch created successfully!";
        } catch (PDOException $e) {
            $error = "Branch Code already exists or database error occurred.";
        }
    } else {
        $error = "All branch fields are required.";
    }
}

// Handle User Creation Form Submission
if (isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // In production, hash it using password_hash()
    $role = $_POST['role'];
    $branch_id = ($role === 'branch_user') ? $_POST['branch_id'] : null;
    if (!empty($username) && !empty($password) && !empty($role)) {
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, branch_id) VALUES (:user, :pass, :role, :branch)");
            $stmt->bindParam(':user', $username);
            $stmt->bindParam(':pass', $password);
            $stmt->bindParam(':role', $role);
            $stmt->bindValue(':branch', $branch_id, $branch_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();
            $message = "User account created successfully!";
        } catch (PDOException $e) {
            $error = "Username already exists.";
        }
    } else {
        $error = "All user fields are required.";
    }
}

// Fetch all branches for the dropdown and list display
$branches_stmt = $conn->query("SELECT * FROM branches ORDER BY branch_name ASC");
$branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script>
        // JavaScript to show/hide branch dropdown based on selected role
        function toggleBranchDropdown() {
            const roleSelect = document.getElementById('user_role');
            const branchContainer = document.getElementById('branch_select_container');
            if (roleSelect.value === 'branch_user') {
                branchContainer.style.display = 'block';
            } else {
                branchContainer.style.display = 'none';
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Top Navigation Bar -->
    <nav class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <h1 class="text-xl font-bold">Admin Dashboard Panel</h1>
        <div class="flex items-center space-x-4">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-1.5 rounded text-sm transition">Logout</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Global Feedback Messages -->
        <div class="col-span-1 md:col-span-2">
            <?php if (!empty($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-2 text-center font-medium"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-2 text-center font-medium"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>

        <!-- SECTION 1: Create Branch Form -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">1. Add New Branch</h2>
            <form action="admin_dashboard.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch Code (Unique)</label>
                    <input type="text" name="branch_code" placeholder="e.g., BR001" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                    <input type="text" name="branch_name" placeholder="e.g., Kukatpally Branch" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" name="create_branch" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded transition">
                    Save Branch
                </button>
            </form>
        </div>

        <!-- SECTION 2: Create User Login Form -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">2. Create User Logins (HO / Branch)</h2>
            <form action="admin_dashboard.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Role</label>
                    <select name="role" id="user_role" onchange="toggleBranchDropdown()" required
                            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="ho_user">Head Office User (HO)</option>
                        <option value="branch_user">Branch User</option>
                    </select>
                </div>

                <!-- Dynamic Dropdown for Branches (Hidden by default, shows only if Branch User is selected) -->
                <div id="branch_select_container" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link to Branch</label>
                    <select name="branch_id" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>">
                                <?php echo htmlspecialchars($branch['branch_name'] . " (" . $branch['branch_code'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="create_user" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded transition">
                    Create User Account
                </button>
            </form>
        </div>

        <!-- SECTION 3: Display Active Branches List -->
        <div class="bg-white p-6 rounded-lg shadow col-span-1 md:col-span-2">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Active Network Branches (Total: <?php echo count($branches); ?>)</h2>
            <div class="overflow-x-auto max-h-60">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <th class="p-3 border-b">ID</th>
                            <th class="p-3 border-b">Branch Code</th>
                            <th class="p-3 border-b">Branch Name</th>
                            <th class="p-3 border-b">Created Date</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-600 divide-y divide-gray-200">
                        <?php if(count($branches) > 0): ?>
                            <?php foreach ($branches as $b): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3"><?php echo $b['id']; ?></td>
                                    <td class="p-3 font-bold text-blue-600"><?php echo htmlspecialchars($b['branch_code']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($b['branch_name']); ?></td>
                                    <td class="p-3"><?php echo $b['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-400">No branches added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>