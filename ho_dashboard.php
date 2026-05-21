<?php
// Include database connection
require_once 'config.php';

// Security Check: Only allow logged-in Head Office (HO) Users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ho_user') {
    header("Location: index.php");
    exit;
}

// Fetch all branches for the filter dropdown
$branches_stmt = $conn->query("SELECT * FROM branches ORDER BY branch_name ASC");
$branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_branch = isset($_GET['branch_filter']) ? $_GET['branch_filter'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Head Office Dashboard - Live</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-indigo-600 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div>
            <h1 class="text-xl font-bold">Head Office Portal 🟢 <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded-full animate-pulse">LIVE STREAMING</span></h1>
            <p class="text-xs text-indigo-200">Central Monitoring & Real-time System</p>
        </div>
        <div class="flex items-center space-x-4">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-1.5 rounded text-sm transition">Logout</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
        
        <!-- FILTER BAR: Branch Selection -->
        <div class="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-md font-bold text-gray-700">Filter Network Records</h2>
                <p class="text-xs text-gray-400">Select a specific branch to view its employees</p>
            </div>
            
            <form action="ho_dashboard.php" method="GET" id="filterForm" class="flex items-center space-x-3 w-full md:w-auto">
                <select name="branch_filter" id="branch_filter" class="w-full md:w-72 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    <option value="">All 130+ Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>" <?php echo ($selected_branch == $branch['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['branch_name'] . " (" . $branch['branch_code'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded text-sm font-medium transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- MAIN DATA TABLE -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                Employee Directory (<span id="record_count">Loading...</span>)
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                            <th class="p-4 border-b">Branch Details</th>
                            <th class="p-4 border-b">Employee Name</th>
                            <th class="p-4 border-b">Designation</th>
                            <th class="p-4 border-b">Mobile Number</th>
                            <th class="p-4 border-b text-center">Quick Actions</th>
                        </tr>
                    </thead>
                    <!-- This ID 'live_employee_table' is where JavaScript inserts rows dynamically -->
                    <tbody id="live_employee_table" class="text-sm text-gray-600 divide-y divide-gray-100">
                        <!-- Data will stream here live via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- LIVE STREAMING JAVASCRIPT LAZIC -->
    <script>
        const filterDropdown = document.getElementById('branch_filter');
        const tableBody = document.getElementById('live_employee_table');
        const recordCountSpan = document.getElementById('record_count');

        // Function to fetch and update UI live
        function streamLiveEntries() {
            const currentFilter = filterDropdown.value;
            
            // Call our new live api file
            fetch(`fetch_live_employees.php?branch_filter=${currentFilter}`)
                .then(response => response.json())
                .then(data => {
                    // Update Total Records count display
                    recordCountSpan.innerText = `Records Found: ${data.length}`;
                    
                    if(data.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-gray-400 font-medium">No employee records found.</td></tr>`;
                        return;
                    }

                    // Loop through the fresh data and generate rows without refreshing page
                    let tableHTML = "";
                    data.forEach(emp => {
                        tableHTML += `
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="p-4">
                                    <div class="font-semibold text-gray-800">${escapeHtml(emp.branch_name)}</div>
                                    <div class="text-xs text-gray-400">${escapeHtml(emp.branch_code)}</div>
                                </td>
                                <td class="p-4 font-medium text-indigo-600">${escapeHtml(emp.employee_name)}</td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full text-xs font-medium">${escapeHtml(emp.designation)}</span></td>
                                <td class="p-4 font-mono">${escapeHtml(emp.mobile_number)}</td>
                                <td class="p-4 text-center">
                                    <div class="inline-flex space-x-2">
                                        <a href="tel:${emp.mobile_number}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 p-2 rounded-full transition shadow-sm border border-emerald-200">📞 Call</a>
                                        <a href="https://wa.me/91${emp.mobile_number}" target="_blank" class="bg-green-50 hover:bg-green-100 text-green-600 p-2 rounded-full transition shadow-sm border border-green-200">💬 WhatsApp</a>
                                        <a href="sms:${emp.mobile_number}" class="bg-blue-50 hover:bg-blue-100 text-blue-600 p-2 rounded-full transition shadow-sm border border-blue-200">✉️ SMS</a>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = tableHTML;
                })
                .catch(error => console.error("Error streaming live data:", error));
        }

        // Helper function to keep data safe from basic XSS injections
        function escapeHtml(text) {
            return text ? text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") : '';
        }

        // Run streaming instantly on load
        streamLiveEntries();

        // 🟢 THE STREAMING TIMER: Fetch data every 3 seconds automatically!
        setInterval(streamLiveEntries, 3000);
    </script>

</body>
</html>