<?php
// Include database connection
require_once 'config.php';

// Security Check: Only allow logged-in Branch Users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'branch_user') {
    header("Location: index.php");
    exit;
}

// SAFE FALLBACK: If session branch_id is missing, pull it directly from the database
if (!isset($_SESSION['branch_id']) || empty($_SESSION['branch_id'])) {
    $user_check_stmt = $conn->prepare("SELECT branch_id FROM users WHERE id = :user_id");
    $user_check_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $user_check_stmt->execute();
    $user_data = $user_check_stmt->fetch(PDO::FETCH_ASSOC);
    $branch_id = $user_data['branch_id'];
    $_SESSION['branch_id'] = $branch_id; // Sync back to session
} else {
    $branch_id = $_SESSION['branch_id'];
}

$message = "";
$error = "";

// Variables for Editing mode
$edit_mode = false;
$edit_id = "";
$edit_name = "";
$edit_designation = "";
$edit_mobile = "";

// 1. HANDLE INSERT (Add New Employee) OR UPDATE (Edit Employee)
if (isset($_POST['save_employee'])) {
    $emp_name = trim($_POST['employee_name']);
    $designation = trim($_POST['designation']);
    $mobile = trim($_POST['mobile_number']);
    $emp_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : '';

    if (!empty($emp_name) && !empty($designation) && !empty($mobile)) {
        if (!empty($emp_id)) {
            // Update Existing Record (Security Check: Ensure employee belongs to this branch)
            try {
                $stmt = $conn->prepare("UPDATE employees SET employee_name = :name, designation = :desig, mobile_number = :mobile WHERE id = :id AND branch_id = :branch_id");
                $stmt->bindParam(':name', $emp_name);
                $stmt->bindParam(':desig', $designation);
                $stmt->bindParam(':mobile', $mobile);
                $stmt->bindParam(':id', $emp_id);
                $stmt->bindParam(':branch_id', $branch_id);
                $stmt->execute();
                $message = "Employee details updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating employee details.";
            }
        } else {
            // Insert New Record
            try {
                $stmt = $conn->prepare("INSERT INTO employees (branch_id, employee_name, designation, mobile_number) VALUES (:branch_id, :name, :desig, :mobile)");
                $stmt->bindParam(':branch_id', $branch_id);
                $stmt->bindParam(':name', $emp_name);
                $stmt->bindParam(':desig', $designation);
                $stmt->bindParam(':mobile', $mobile);
                $stmt->execute();
                $message = "New employee added successfully!";
            } catch (PDOException $e) {
                // Display the raw database error message for accurate troubleshooting
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "All fields are required.";
    }
}

// 2. HANDLE EDIT BUTTON CLICK (Fetch data into form)
if (isset($_GET['edit'])) {
    $target_id = $_GET['edit'];
    // Ensure user can only fetch employee from their own branch
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = :id AND branch_id = :branch_id LIMIT 1");
    $stmt->bindParam(':id', $target_id);
    $stmt->bindParam(':branch_id', $branch_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        $edit_mode = true;
        $edit_id = $emp['id'];
        $edit_name = $emp['employee_name'];
        $edit_designation = $emp['designation'];
        $edit_mobile = $emp['mobile_number'];
    }
}

// 3. HANDLE DELETE ACTION
if (isset($_GET['delete'])) {
    $target_id = $_GET['delete'];
    try {
        // Security Check: Ensure employee belongs to this branch before deleting
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = :id AND branch_id = :branch_id");
        $stmt->bindParam(':id', $target_id);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->execute();
        $message = "Employee deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting employee.";
    }
}

// 4. FETCH BRANCH NAME FOR DISPLAY
$branch_stmt = $conn->prepare("SELECT branch_name, branch_code FROM branches WHERE id = :id");
$branch_stmt->bindParam(':id', $branch_id);
$branch_stmt->execute();
$current_branch = $branch_stmt->fetch(PDO::FETCH_ASSOC);

// 5. FETCH EMPLOYEES BELONGING ONLY TO THIS BRANCH
$employees_stmt = $conn->prepare("SELECT * FROM employees WHERE branch_id = :branch_id ORDER BY id DESC");
$employees_stmt->bindParam(':branch_id', $branch_id);
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-teal-600 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div>
            <h1 class="text-xl font-bold">Branch Portal</h1>
            <p class="text-xs text-teal-100">Logged in at: <?php echo htmlspecialchars($current_branch['branch_name'] . " (" . $current_branch['branch_code'] . ")"); ?></p>
        </div>
        <div class="flex items-center space-x-4">
            <span>User: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-1.5 rounded text-sm transition">Logout</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Alerts Block -->
        <div class="col-span-1 md:col-span-3">
            <?php if (!empty($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-2 text-center font-medium"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-2 text-center font-medium"><?php echo $error; ?></div>
            <?php endif; ?>
        </div>

        <!-- FORM SECTION: Add / Edit Employee -->
        <div class="bg-white p-6 rounded-lg shadow col-span-1">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <?php echo $edit_mode ? "Modify Employee Details" : "Add Employee Details"; ?>
            </h2>
            <form action="branch_dashboard.php" method="POST" class="space-y-4">
                
                <!-- Hidden input to track ID during update mode -->
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="employee_id" value="<?php echo $edit_id; ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                    <input type="text" name="employee_name" value="<?php echo htmlspecialchars($edit_name); ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" value="<?php echo htmlspecialchars($edit_designation); ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                    <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($edit_mobile); ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <button type="submit" name="save_employee" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 rounded transition">
                    <?php echo $edit_mode ? "Update Details" : "Submit Details"; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="branch_dashboard.php" class="block text-center text-sm text-gray-500 hover:underline mt-2">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- LIST SECTION: View, Edit, Delete Branch Employees -->
        <div class="bg-white p-6 rounded-lg shadow col-span-1 md:col-span-2">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Registered Employees List (Total: <?php echo count($employees); ?>)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <th class="p-3 border-b">Employee Name</th>
                            <th class="p-3 border-b">Designation</th>
                            <th class="p-3 border-b">Mobile Number</th>
                            <th class="p-3 border-b text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-600 divide-y divide-gray-200">
                        <?php if (count($employees) > 0): ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 font-medium text-gray-900"><?php echo htmlspecialchars($emp['employee_name']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($emp['designation']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($emp['mobile_number']); ?></td>
                                    <td class="p-3 text-center space-x-2">
                                        <!-- Edit button triggers GET parameter to fill the form -->
                                        <a href="branch_dashboard.php?edit=<?php echo $emp['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 font-semibold text-xs bg-blue-50 px-2 py-1 rounded">Edit</a>
                                        <!-- Delete button with native javascript confirmation prompt -->
                                        <a href="branch_dashboard.php?delete=<?php echo $emp['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this employee?');"
                                           class="text-red-600 hover:text-red-800 font-semibold text-xs bg-red-50 px-2 py-1 rounded">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-400">No employees registered from this branch yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>