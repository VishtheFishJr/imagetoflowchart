<?php

require_once 'db.php';

session_name("PHPSESSID");
session_start();

/*
|--------------------------------------------------------------------------
| Make sure user is logged in
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Get current user information
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT username, email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_unset();
        session_destroy();

        header("Location: index.php");
        exit;
    }

} catch (PDOException $e) {

    $error = "Unable to load account information.";
}

/*
|--------------------------------------------------------------------------
| Handle settings changes
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Change Username
    |--------------------------------------------------------------------------
    */

    if ($action === "change_username") {

        $newUsername = trim($_POST["username"] ?? "");

        if ($newUsername === "") {

            $error = "Please enter a username.";

        } elseif (strlen($newUsername) < 3) {

            $error = "Username must be at least 3 characters.";

        } else {

            try {

                /*
                 * Check whether another account already uses this username.
                 */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE username = ?
                    AND id != ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $newUsername,
                    $userId
                ]);

                if ($stmt->fetch()) {

                    $error = "That username is already in use.";

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET username = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $newUsername,
                        $userId
                    ]);

                    /*
                     * Update the current session too.
                     */

                    $_SESSION["username"] = $newUsername;

                    $user["username"] = $newUsername;

                    $success = "Username changed successfully.";
                }

            } catch (PDOException $e) {

                $error = "Something went wrong while changing your username.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Change Email
    |--------------------------------------------------------------------------
    */ elseif ($action === "change_email") {

        $newEmail = trim($_POST["email"] ?? "");

        if ($newEmail === "") {

            $error = "Please enter an email address.";

        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            try {

                /*
                 * Check whether another account already uses this email.
                 */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ?
                    AND id != ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $newEmail,
                    $userId
                ]);

                if ($stmt->fetch()) {

                    $error = "That email address is already in use.";

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET email = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $newEmail,
                        $userId
                    ]);

                    $user["email"] = $newEmail;

                    $success = "Email changed successfully.";
                }

            } catch (PDOException $e) {

                $error = "Something went wrong while changing your email.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Account
    |--------------------------------------------------------------------------
    */ elseif ($action === "delete_account") {

        try {

            /*
             * Start a transaction so everything succeeds together.
             */

            $pdo->beginTransaction();

            /*
             * Delete generated content belonging to this user.
             */

            $stmt = $pdo->prepare("
                DELETE FROM generated_items
                WHERE user_id = ?
            ");

            $stmt->execute([$userId]);

            /*
             * Delete the account itself.
             */

            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE id = ?
            ");

            $stmt->execute([$userId]);

            /*
             * Finish the database transaction.
             */

            $pdo->commit();

            /*
             * Completely log the user out.
             */

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    "",
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();

            /*
             * Send user back to the app.
             */

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            /*
             * Undo any partial database changes.
             */

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to delete your account. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Settings - CamCharts</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            background: #f5f5f5;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #111;
            padding-bottom: 70px;
            transition: background .2s, color .2s;
        }

        /* TOPBAR */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 0 25px;
            z-index: 1000;
        }

        .app-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.5px;
            color: #111;
        }

        .topbar-spacer {
            flex: 1;
        }

        .auth-button {
            display: inline-block;
            padding: 8px 16px;
            background: #111;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .auth-button:hover {
            background: #333;
        }

        /* PAGE & CARDS */
        .page {
            width: 100%;
            max-width: 760px;
            margin: 90px auto 40px;
            padding: 0 20px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 28px;
            font-size: 15px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            transition: background .2s, border-color .2s;
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .description {
            color: #666;
            margin: 0 0 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        /* INPUTS */
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            background: #ffffff;
            color: #111;
            margin-bottom: 14px;
            transition: border-color .2s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
        }

        /* BUTTONS */
        .button,
        .password-button {
            display: inline-block;
            border: none;
            border-radius: 5px;
            padding: 10px 18px;
            background: #2563eb;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s;
        }

        .button:hover,
        .password-button:hover {
            background: #1d4ed8;
        }

        .delete-button {
            border: none;
            border-radius: 5px;
            padding: 10px 18px;
            background: #dc2626;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .delete-button:hover {
            background: #b91c1c;
        }

        .danger-card {
            border-color: #fecaca;
        }

        .danger-card h2 {
            color: #b91c1c;
        }

        /* MESSAGES */
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            border: 1px solid #fecaca;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            border: 1px solid #bbf7d0;
        }

        /* THEME TOGGLE */
        .theme-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .theme-info h2 {
            margin-bottom: 5px;
        }

        .theme-info p {
            margin: 0;
        }

        .theme-switch {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .theme-switch input {
            display: none;
        }

        .theme-slider {
            width: 46px;
            height: 24px;
            background: #ccc;
            border-radius: 24px;
            position: relative;
            transition: background .2s;
            border: 1px solid #aaa;
            display: block;
        }

        .theme-slider::before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            left: 2px;
            top: 2px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
        }

        .theme-switch input:checked+.theme-slider {
            background: #2563eb;
            border-color: #1d4ed8;
        }

        .theme-switch input:checked+.theme-slider::before {
            transform: translateX(22px);
        }

        .theme-label {
            font-size: 14px;
            font-weight: 600;
            color: #111;
            white-space: nowrap;
        }

        /* BOTTOM BAR */
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
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .bottom-bar .settings-gear-link {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            text-decoration: none;
            transition: color 0.2s, transform 0.2s;
        }

        .bottom-bar .settings-gear-link:hover {
            color: #1d4ed8;
            transform: translateY(-50%) rotate(30deg);
        }

        .bottom-bar a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .bottom-bar a:hover {
            text-decoration: underline;
        }

        /* DARK MODE STYLES */
        body.dark-mode {
            background: #111;
            color: #eee;
        }

        body.dark-mode .topbar {
            background: #181818;
            border-bottom-color: #333;
        }

        body.dark-mode .app-title {
            color: #eee;
        }

        body.dark-mode .auth-button {
            background: #eee;
            color: #111;
        }

        body.dark-mode .auth-button:hover {
            background: #ddd;
        }

        body.dark-mode .subtitle,
        body.dark-mode .description {
            color: #aaa;
        }

        body.dark-mode .card {
            background: #181818;
            border-color: #333;
        }

        body.dark-mode input[type="text"],
        body.dark-mode input[type="email"] {
            background: #111;
            color: #eee;
            border-color: #555;
        }

        body.dark-mode .theme-label {
            color: #eee;
        }

        body.dark-mode .danger-card {
            border-color: #7f1d1d;
        }

        body.dark-mode .bottom-bar {
            background: #181818;
            border-top-color: #333;
            color: #cbd5e1;
        }

        body.dark-mode .bottom-bar a {
            color: #60a5fa;
        }

        body.dark-mode .bottom-bar .settings-gear-link {
            color: #60a5fa;
        }

        @media (max-width: 600px) {
            .page {
                margin-top: 75px;
            }
        }
    </style>

</head>

<body>

    <div class="topbar">
        <div class="app-title">
            CamCharts
        </div>
        <div class="topbar-spacer"></div>
        <a href="index.php" class="auth-button">
            &larr; Back to App
        </a>
    </div>

    <main class="page">

        <h1>Settings</h1>

        <p class="subtitle">
            Manage your account and application preferences.
        </p>

        <?php if ($error): ?>

            <div class="error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="success">
                <?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?>
            </div>

        <?php endif; ?>

        <!-- Username -->
        <div class="card">
            <h2>Username</h2>
            <p class="description">
                Change the username associated with your account.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="change_username">
                <input type="text" name="username" value="<?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?>" minlength="3" required autocomplete="username">
                <button type="submit" class="button">
                    Change Username
                </button>
            </form>
        </div>

        <!-- Email -->
        <div class="card">
            <h2>Email Address</h2>
            <p class="description">
                Change the email address associated with your account.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="change_email">
                <input type="email" name="email" value="<?php echo htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8"); ?>" required autocomplete="email">
                <button type="submit" class="button">
                    Change Email
                </button>
            </form>
        </div>

        <!-- Password -->
        <div class="card">
            <h2>Password</h2>
            <p class="description">
                Change your password using the password reset page.
            </p>
            <a href="forgot_password.php" class="password-button">
                Change Password
            </a>
        </div>

        <!-- Theme -->
        <div class="card">
            <div class="theme-row">
                <div class="theme-info">
                    <h2>Appearance</h2>
                    <p class="description">
                        Switch between light mode and dark mode.
                    </p>
                </div>
                <label class="theme-switch" title="Toggle dark mode">
                    <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()">
                    <span class="theme-slider"></span>
                    <span class="theme-label" id="themeLabel">Light</span>
                </label>
            </div>
        </div>

        <!-- Delete Account -->
        <div class="card danger-card">
            <h2>Delete Account</h2>
            <p class="description">
                Permanently delete your account and the content associated with it. This action cannot be undone.
            </p>
            <form method="POST" onsubmit="return confirmDelete();">
                <input type="hidden" name="action" value="delete_account">
                <button type="submit" class="delete-button">
                    Delete Account
                </button>
            </form>
        </div>

    </main>

    <div class="bottom-bar">
        <a href="settings.php" class="settings-gear-link" title="Settings" aria-label="Settings">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </a>
        Any issues or questions? Please email
        <a href="mailto:camchartssupport@gmail.com">camchartssupport@gmail.com</a>
        &nbsp;|&nbsp;
        <a href="privacy.php">Privacy Policy</a>
        &nbsp;|&nbsp;
        <a href="terms.php">Terms of Service</a>
    </div>

    <script>
        function applyTheme() {
            const isDark = localStorage.getItem("aiStudyScannerDarkMode") === "1" || localStorage.getItem("darkMode") === "true";

            document.body.classList.toggle("dark-mode", isDark);

            const toggle = document.getElementById("darkModeToggle");
            const label = document.getElementById("themeLabel");

            if (toggle) {
                toggle.checked = isDark;
            }

            if (label) {
                label.textContent = isDark ? "Dark" : "Light";
            }
        }

        function toggleDarkMode() {
            const toggle = document.getElementById("darkModeToggle");
            const isDark = toggle.checked;

            localStorage.setItem("aiStudyScannerDarkMode", isDark ? "1" : "0");
            localStorage.setItem("darkMode", isDark ? "true" : "false");

            applyTheme();
        }

        function confirmDelete() {
            return confirm(
                "Are you sure you want to permanently delete your account and all of its content? This cannot be undone."
            );
        }

        applyTheme();
    </script>

</body>

</html>