<?php

require_once 'db.php';
require_once 'config.php';

// Ensure password_resets table exists
try {
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
} catch (PDOException $e) {
    // Keep going if table already exists or permission issues
}

// Helper: send email via authenticated SMTP
function sendCamChartsEmail($toEmail, $subject, $bodyText) {
    $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
    $smtpPort = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
    $smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';
    $fromName = 'CamCharts Support';

    error_log("CamCharts email: to=$toEmail host=$smtpHost port=$smtpPort user=$smtpUser pass_set=" . (!empty($smtpPass) ? 'YES' : 'NO'));

    if ($smtpHost && $smtpUser && $smtpPass) {
        $sent = sendSmtpEmail($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpUser, $fromName, $toEmail, $subject, $bodyText);
        if ($sent) {
            error_log("CamCharts email: SMTP send SUCCESS");
            return true;
        }
        error_log("CamCharts email: SMTP send FAILED, trying mail()");
    } else {
        error_log("CamCharts email: SMTP creds missing, trying mail()");
    }

    // Fallback
    $headers = "From: $fromName <$smtpUser>\r\nReply-To: $smtpUser\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $result = @mail($toEmail, $subject, $bodyText, $headers);
    error_log("CamCharts email: mail() result=" . ($result ? 'true' : 'false'));
    return $result;
}

function smtpRead($socket) {
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) break;
        $response .= $line;
        // SMTP multi-line response ends when 4th char is a space (not a dash)
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return trim($response);
}

function sendSmtpEmail($host, $port, $user, $pass, $fromEmail, $fromName, $toEmail, $subject, $bodyText) {
    $timeout = 20;
    $errno = 0; $errstr = '';

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ]);

    // Port 465 = implicit SSL, port 587 = STARTTLS
    $socketAddr = ($port == 465 ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($socketAddr, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        error_log("SMTP connect failed: $socketAddr — $errstr ($errno)");
        return false;
    }
    stream_set_timeout($socket, $timeout);

    $banner = smtpRead($socket);
    error_log("SMTP banner: $banner");
    if (substr($banner, 0, 3) !== '220') { fclose($socket); return false; }

    // Send EHLO
    fwrite($socket, "EHLO " . (gethostname() ?: 'localhost') . "\r\n");
    $ehlo = smtpRead($socket);
    error_log("SMTP EHLO: $ehlo");

    // STARTTLS upgrade for port 587
    if ($port == 587) {
        fwrite($socket, "STARTTLS\r\n");
        $tls = smtpRead($socket);
        error_log("SMTP STARTTLS: $tls");
        if (substr($tls, 0, 3) !== '220') { fclose($socket); return false; }

        $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        if (!stream_socket_enable_crypto($socket, true, $crypto)) {
            error_log("SMTP TLS handshake failed");
            fclose($socket);
            return false;
        }

        // Re-issue EHLO after TLS
        fwrite($socket, "EHLO " . (gethostname() ?: 'localhost') . "\r\n");
        $ehlo2 = smtpRead($socket);
        error_log("SMTP EHLO2: $ehlo2");
    }

    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    $auth = smtpRead($socket);
    error_log("SMTP AUTH LOGIN: $auth");

    fwrite($socket, base64_encode($user) . "\r\n");
    $userRes = smtpRead($socket);
    error_log("SMTP user prompt: $userRes");

    fwrite($socket, base64_encode($pass) . "\r\n");
    $authRes = smtpRead($socket);
    error_log("SMTP auth result: $authRes");

    if (substr($authRes, 0, 3) !== '235') {
        error_log("SMTP auth rejected: $authRes");
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return false;
    }

    // Envelope
    fwrite($socket, "MAIL FROM: <$fromEmail>\r\n");
    $mf = smtpRead($socket);
    error_log("SMTP MAIL FROM: $mf");

    fwrite($socket, "RCPT TO: <$toEmail>\r\n");
    $rt = smtpRead($socket);
    error_log("SMTP RCPT TO: $rt");

    fwrite($socket, "DATA\r\n");
    $data = smtpRead($socket);
    error_log("SMTP DATA: $data");

    // Build message
    $msg  = "From: $fromName <$fromEmail>\r\n";
    $msg .= "To: <$toEmail>\r\n";
    $msg .= "Subject: $subject\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Date: " . date('r') . "\r\n";
    $msg .= "\r\n";
    $msg .= $bodyText . "\r\n.";

    fwrite($socket, $msg . "\r\n");
    $sent = smtpRead($socket);
    error_log("SMTP send result: $sent");

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return substr($sent, 0, 3) === '250';
}

$error = "";
$info = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, email
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // Remove existing tokens for this email
                $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->execute([$email]);

                // Store new reset token
                $ins = $pdo->prepare("
                    INSERT INTO password_resets (email, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $ins->execute([$email, $token, $expiresAt]);

                $resetUrl = "https://vishthefishjr.me/password_reset.php?token=" . urlencode($token);

                $subject = "Password Reset";
                $body = "To reset your password for CamCharts, please visit the following link:\n\n" . $resetUrl;

                $sent = sendCamChartsEmail($email, $subject, $body);

                if (!$sent) {
                    error_log("Failed to send password reset email to $email.");
                }
            }

            $info = "If an account with that email exists, a password reset email has been sent. Please check your Inbox and Spam/Junk folder.";

        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CamCharts</title>
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
            line-height: 1.4;
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

        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.4;
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
        <h1>Forgot Password</h1>
        <div class="subtitle">
            Enter your email address and we'll send you a link to reset your password.
        </div>

        <?php if ($info): ?>
            <div class="info">
                <?php echo htmlspecialchars($info, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required autocomplete="email" value="<?php
                echo htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8");
            ?>">

            <button type="submit">Send Reset Link</button>
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
