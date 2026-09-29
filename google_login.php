<?php

require_once 'vendor/autoload.php';

session_start();

$client = new Google_Client();

$client->setAuthConfig('client_secret.json');

$client->setRedirectUri(
    'https://vishthefishjr.me/oauth_callback.php'
);

$client->addScope(
    Google_Service_Slides::PRESENTATIONS
);

$client->addScope(
    Google_Service_Drive::DRIVE_FILE
);

$client->addScope(
    'https://www.googleapis.com/auth/forms.body'
);

$client->addScope(
    'https://www.googleapis.com/auth/spreadsheets'
);

// Important: keep login active
$client->setAccessType('offline');

$url = $client->createAuthUrl();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connect Google Account - AI Study Scanner</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                Arial, Helvetica, sans-serif;

            background: #fff;
            color: #111;

            transition: background 0.2s, color 0.2s;
        }

        /* ------------------------------------------------------------
           TOP BAR
        ------------------------------------------------------------ */

        .topbar {
            height: 64px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;

            display: flex;
            align-items: center;

            padding: 0 22px;

            background: #fff;
            border-bottom: 1px solid #ddd;

            z-index: 1000;
        }

        .app-title {
            font-size: 20px;
            font-weight: 700;
        }

        .topbar-right {
            margin-left: auto;

            display: flex;
            align-items: center;

            gap: 12px;
        }

        /* ------------------------------------------------------------
           SIDEBAR
        ------------------------------------------------------------ */

        .sidebar {
            position: fixed;

            top: 64px;
            left: 0;
            bottom: 0;

            width: 220px;

            background: #f5f5f5;
            border-right: 1px solid #ddd;

            padding: 20px 14px;

            z-index: 900;
        }

        .sidebar-title {
            font-size: 13px;
            font-weight: 600;

            color: #666;

            margin: 0 10px 12px;
            text-transform: uppercase;
        }

        .sidebar-link {
            display: block;

            padding: 10px 12px;
            margin-bottom: 4px;

            color: #111;
            text-decoration: none;

            border-radius: 6px;

            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-link:hover {
            background: #e5e5e5;
        }

        /* ------------------------------------------------------------
           DARK MODE SWITCH
        ------------------------------------------------------------ */

        .theme-container {
            margin-top: 25px;

            padding: 12px;

            border-top: 1px solid #ddd;
        }

        .theme-label {
            font-size: 14px;
            font-weight: 600;

            margin-bottom: 10px;
        }

        .theme-switch {
            position: relative;

            display: inline-block;

            width: 48px;
            height: 26px;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;

            cursor: pointer;

            inset: 0;

            background: #ccc;

            border-radius: 20px;

            transition: 0.2s;
        }

        .slider:before {
            content: "";

            position: absolute;

            width: 20px;
            height: 20px;

            left: 3px;
            top: 3px;

            background: white;

            border-radius: 50%;

            transition: 0.2s;
        }

        input:checked+.slider {
            background: #111;
        }

        input:checked+.slider:before {
            transform: translateX(22px);
        }

        /* ------------------------------------------------------------
           MAIN CONTENT
        ------------------------------------------------------------ */

        .main {
            margin-left: 220px;

            padding: 104px 30px 40px;

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .connect-card {
            width: 100%;
            max-width: 600px;

            border: 1px solid #ddd;

            background: #fff;

            padding: 40px;

            margin-top: 40px;

            text-align: center;
        }

        .connect-card h1 {
            margin: 0 0 12px;

            font-size: 28px;
            font-weight: 700;
        }

        .connect-card p {
            margin: 0 auto 28px;

            max-width: 480px;

            color: #666;

            font-size: 15px;
            line-height: 1.6;
        }

        .google-button {
            display: inline-block;

            padding: 11px 20px;

            background: #111;
            color: #fff;

            border: 1px solid #111;
            border-radius: 6px;

            text-decoration: none;

            font-size: 15px;
            font-weight: 600;

            transition: background 0.2s;
        }

        .google-button:hover {
            background: #333;
        }

        /* ------------------------------------------------------------
           DARK MODE
        ------------------------------------------------------------ */

        body.dark-mode {
            background: #111;
            color: #eee;
        }

        body.dark-mode .topbar {
            background: #111;
            border-color: #333;
        }

        body.dark-mode .sidebar {
            background: #151515;
            border-color: #333;
        }

        body.dark-mode .sidebar-title {
            color: #999;
        }

        body.dark-mode .sidebar-link {
            color: #eee;
        }

        body.dark-mode .sidebar-link:hover {
            background: #292929;
        }

        body.dark-mode .theme-container {
            border-color: #333;
        }

        body.dark-mode .connect-card {
            background: #181818;
            border-color: #333;
        }

        body.dark-mode .connect-card p {
            color: #aaa;
        }

        body.dark-mode .google-button {
            background: #eee;
            color: #111;
            border-color: #eee;
        }

        body.dark-mode .google-button:hover {
            background: #ccc;
        }

        /* ------------------------------------------------------------
           MOBILE
        ------------------------------------------------------------ */

        @media (max-width: 700px) {

            .sidebar {
                width: 70px;
                padding: 20px 8px;
            }

            .sidebar-title,
            .sidebar-link span,
            .theme-label {
                display: none;
            }

            .sidebar-link {
                text-align: center;
                padding: 12px 5px;
            }

            .theme-container {
                text-align: center;
            }

            .main {
                margin-left: 70px;
                padding: 94px 20px 30px;
            }

            .connect-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- TOP BAR -->
    <div class="topbar">

        <div class="app-title">
            AI Study Scanner
        </div>

        <div class="topbar-right">
            <!-- Keep any existing topbar buttons from index.php here -->
        </div>

    </div>


    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="sidebar-title">
            Navigation
        </div>

        <a href="index.php" class="sidebar-link">
            🏠 <span>Home</span>
        </a>

        <a href="get_items.php" class="sidebar-link">
            📁 <span>My Files</span>
        </a>

        <div class="theme-container">

            <div class="theme-label">
                Dark Mode
            </div>

            <label class="theme-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>

        </div>

    </div>


    <!-- MAIN -->
    <main class="main">

        <div class="connect-card">

            <h1>Connect Google Account</h1>

            <p>
                Connect your Google account to create editable
                Google Slides, Forms, and Sheets directly from
                AI Study Scanner.
            </p>

            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="google-button">
                Connect Google Account
            </a>

        </div>

    </main>


    <script>
        const toggle = document.getElementById("darkModeToggle");

        // Restore saved theme
        if (localStorage.getItem("aiStudyScannerDarkMode") === "true") {
            document.body.classList.add("dark-mode");
            toggle.checked = true;
        }

        // Save theme
        toggle.addEventListener("change", function () {

            if (this.checked) {
                document.body.classList.add("dark-mode");
                localStorage.setItem("aiStudyScannerDarkMode", "true");
            } else {
                document.body.classList.remove("dark-mode");
                localStorage.setItem("aiStudyScannerDarkMode", "false");
            }

        });
    </script>

</body>

</html>