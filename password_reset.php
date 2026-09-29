<?php

require_once 'db.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

// If user visits password_reset.php without having the valid link/token, route to login.php automatically
if ($token === '') {
    header("Location: login.php");
    exit;
}

$resetRecord = null;

try {
    // Ensure table exists just in case
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->prepare("
        SELECT id, email, expires_at
        FROM password_resets
        WHERE token = ? AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRecord) {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($password === "" || $confirmPassword === "") {
        $error = "Please enter and confirm your new password.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $userEmail = $resetRecord["email"];

            // Update user's password in the users table
            $update = $pdo->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE email = ?
            ");
            $update->execute([$passwordHash, $userEmail]);

            // Clear reset token
            $del = $pdo->prepare("
                DELETE FROM password_resets
                WHERE email = ?
            ");
            $del->execute([$userEmail]);

            $pdo->commit();

            // Take back to login.php
            header("Location: login.php?reset=success");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Could not reset password. Please try again.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CamCharts</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            padding-bottom: 60px;
        }

        .container {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .bottom {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .bottom a {
            color: #2563eb;
            text-decoration: none;
        }

        .bottom a:hover {
            text-decoration: underline;
        }

        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 12px 20px;
            text-align: center;
            font-size: 14px;
            color: #334155;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .bottom-bar a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .bottom-bar a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Reset Password</h1>
        <div class="subtitle">
            Enter your new password below.
        </div>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, "UTF-8"); ?>">

            <label for="password">New Password</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">

            <button type="submit">Save</button>
        </form>

        <div class="bottom">
            Remembered your password?
            <a href="login.php">Log in</a>
        </div>
    </div>

    <div class="bottom-bar">
        Any issues or questions? Please email <a href="mailto:camchartssupport@gmail.com">camchartssupport@gmail.com</a>!
    </div>

</body>
</html>
