<?php
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$error = " ";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        log_system_event($conn, 'ANONYMOUS', 'CSRF_VALIDATION_FAILURE');
        die("Security token validation failed.");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT id, password, login_attempts, lock_until FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $login_attempts, $lock_until);
        $stmt->fetch();
        
        if ($lock_until && $lock_until > $now) {
            log_system_event($conn, $username, 'LOGIN_REJECTED_ACCOUNT_LOCKED');
            $error = "Account locked due to multiple failed attempts. Try again later.";
        } else {
            if (password_verify($password, $hashed_password)) {
                $reset_stmt = $conn->prepare("UPDATE users SET login_attempts = 0, lock_until = NULL WHERE id = ?");
                $reset_stmt->bind_param("i", $id);
                $reset_stmt->execute();
                $reset_stmt->close();

                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['last_activity'] = time();

                log_system_event($conn, $username, 'USER_LOGIN_SUCCESSFUL');
                header("Location: home.php");
                exit;
            } else {
                $login_attempts++;
                if ($login_attempts >= 5) {
                    $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $lock_stmt = $conn->prepare("UPDATE users SET login_attempts = ?, lock_until = ? WHERE id = ?");
                    $lock_stmt->bind_param("isi", $login_attempts, $lock_time, $id);
                    $lock_stmt->execute();
                    $lock_stmt->close();
                    log_system_event($conn, $username, 'SECURITY_ACCOUNT_LOCKED_LIMIT_EXCEEDED');
                    $error = "Account locked for 15 minutes due to 5 consecutive failed attempts.";
                } else {
                    $update_stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $login_attempts, $id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    log_system_event($conn, $username, 'LOGIN_FAILED_BAD_CREDENTIALS');
                    $error = "Invalid username or password configuration.";
                }
            }
        }
    } else {
        log_system_event($conn, 'ANONYMOUS', 'LOGIN_ATTEMPT_NONEXISTENT_USER');
        $error = "Invalid username or password configuration.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Portal Access</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="modal active" style="position: relative; background: transparent; backdrop-filter: none;">
        <div class="modal-content" style="max-width: 380px; margin: 0 auto;">
            <h2 style="text-align: center; color: #38bdf8; margin-bottom: 20px;">Secure Gateway Login</h2>
            <?php if (!empty(trim($error))) echo "<div style='color: #f87171; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.2); padding: 10px; font-size: 14px; border-radius: 4px; margin-bottom: 15px; text-align: center;'>".htmlspecialchars($error)."</div>"; ?>
            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username Identifier" required autocomplete="off">
                <label>Password</label>
                <input type="password" name="password" placeholder="System Security Password" required>
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Authenticate</button>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <a href="register.php" style="color: #38bdf8; text-decoration: none; font-size: 14px;">Create Administrative Node</a>
            </div>
        </div>
    </div>
</body>
</html>