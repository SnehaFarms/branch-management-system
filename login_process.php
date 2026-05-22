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

                // Verify password (హాష్ పాస్‌వర్డ్ లేదా డైరెక్ట్ టెక్స్ట్ రెండింటినీ చెక్ చేస్తుంది)
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    
                    // Regenerate session ID for security purposes
                    session_regenerate_id(true);

                    // Store important user info in Session (ఒకవేళ కాలమ్స్ లేకపోతే ఎర్రర్ రాకుండా ఇక్కడ చెక్ పెట్టాను)
                    $_SESSION['user_id'] = isset($user['id']) ? $user['id'] : 1;
                    $_SESSION['username'] = $user['username'];
                    
                    // ఒకవేళ డేటాబేస్ టేబుల్ లో role లేకపోతే డిఫాల్ట్ గా 'admin' అని తీసుకుంటుంది
                    $user_role = isset($user['role']) ? $user['role'] : 'admin';
                    $_SESSION['role'] = $user_role;
                    
                    if (isset($user['branch_id'])) {
                        $_SESSION['branch_id'] = $user['branch_id'];
                    }

                    // Redirect based on User Role లేదా యూజర్ నేమ్ admin అయితే నేరుగా అడ్మిన్ డాష్‌బోర్డ్‌కి వెళ్తుంది
                    if ($user_role === 'admin' || $username === 'admin') {
                        header("Location: admin_dashboard.php");
                        exit;
                    } elseif ($user_role === 'ho_user') {
                        header("Location: ho_dashboard.php");
                        exit;
                    } elseif ($user_role === 'branch_user') {
                        header("Location: branch_dashboard.php");
                        exit;
                    } else {
                        header("Location: admin_dashboard.php");
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
            // ఒకవేళ డేటాబేస్ ఎర్రర్ వస్తే ఇండెక్స్ పేజీలో ఆ మెసేజ్ కనిపిస్తుంది
            $_SESSION['login_error'] = "Database Error: " . $e->getMessage();
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
