<?php
require 'db.php';

$message = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security token validation failed.");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (strlen($password) < 12 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[^a-zA-Z0-9]/', $password)) {
        
        $message = "Registration Rejected: Password strength policy violation.";
        $status = "error";
    } else if (!empty($username)) {
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Identity identifier unavailable.";
            $status = "error";
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                log_system_event($conn, $username, 'USER_ACCOUNT_CREATION_SUCCESSFUL');
                $message = "Identity created successfully.";
                $status = "success";
            } else {
                $message = "Fatal database operational anomaly.";
                $status = "error";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Authority Node</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="modal active" style="position: relative; background: transparent; backdrop-filter: none;">
        <div class="modal-content" style="max-width: 420px; margin: 0 auto;">
            <h2 style="text-align: center; color: #10b981; margin-bottom: 20px;">Register Node</h2>
            <?php 
            if ($status === "success") echo "<div style='color: #34d399; background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.2); padding: 10px; font-size: 14px; border-radius: 4px; margin-bottom: 15px; text-align: center;'>".htmlspecialchars($message)." <a href='index.php' style='color:#38bdf8;'>Sign In</a></div>";
            if ($status === "error") echo "<div style='color: #f87171; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.2); padding: 10px; font-size: 14px; border-radius: 4px; margin-bottom: 15px; text-align: center;'>".htmlspecialchars($message)."</div>";
            ?>
            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <label>Desired Username</label>
                <input type="text" name="username" placeholder="Desired Username" required autocomplete="off">
                <label>Password Policy (Min 12 characters, Mixed Case, Symbols)</label>
                <input type="password" name="password" placeholder="Password Requirements" required>
                <button type="submit" class="btn-primary" style="width: 100%; background-color: #10b981; margin-top: 10px;">Provision Profile</button>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" style="color: #38bdf8; text-decoration: none; font-size: 14px;">Back to Entrance Gateway</a>
            </div>
        </div>
    </div>
</body>
</html>