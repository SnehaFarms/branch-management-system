<?php
// Include configuration file
require_once 'config.php';

// If user is already logged in, redirect them to their respective dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'ho_user') {
        header("Location: ho_dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'branch_user') {
        header("Location: branch_dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Management System</title>
    <!-- Tailwind CSS for modern and clean UI -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Account Login</h2>

        <!-- Display error message if login fails -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm text-center">
                <?php 
                    echo $_SESSION['login_error']; 
                    unset($_SESSION['login_error']); // Clear message after displaying
                ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login_process.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" name="login_submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-md transition duration-200">
                Sign In
            </button>
        </form>
    </div>

</body>
</html>