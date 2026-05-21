<?php
// Include database configuration
require_once 'config.php';

// Check if the form is submitted
if (isset($_POST['login_submit'])) {
    
    // Sanitize user inputs to prevent basic malicious data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            // Prepare SQL query to find the user
            $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Check if user exists
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password (In production, use password_verify with hashed passwords)
                // For direct matching initially: $password === $user['password']
                // We are using password_verify assuming passwords will be hashed
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    
                    // Regenerate session ID for security purposes
                    session_regenerate_id(true);

                    // Store important user info in Session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['branch_id'] = $user['branch_id'];

                    // Redirect based on User Role (The Redirection Magic)
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'ho_user') {
                        header("Location: ho_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'branch_user') {
                        header("Location: branch_dashboard.php");
                        exit;
                    }
                } else {
                    // Password does not match
                    $_SESSION['login_error'] = "Invalid username or password.";
                    header("Location: index.php");
                    exit;
                }
            } else {
                // User not found
                $_SESSION['login_error'] = "Invalid username or password.";
                header("Location: index.php");
                exit;
            }

        } catch (PDOException $e) {
            $_SESSION['login_error'] = "Something went wrong. Please try again.";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: index.php");
        exit;
    }
} else {
    // If someone tries to access this file directly without clicking login button
    header("Location: index.php");
    exit;
}
?>