<?php

require_once 'db.php';


/*
 * LOGOUT
 */

if (
    isset($_GET["logout"]) &&
    $_GET["logout"] === "1"
) {

    $_SESSION = [];

    if (
        ini_get("session.use_cookies")
    ) {

        $params =
            session_get_cookie_params();

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

    header("Location: login.php");

    exit;
}


$error = "";
$success = "";

if (isset($_GET["reset"]) && $_GET["reset"] === "success") {
    $success = "Your password has been reset successfully! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login =
        trim(
            $_POST["login"] ?? ""
        );

    $password =
        $_POST["password"] ?? "";


    if (
        $login === "" ||
        $password === ""
    ) {

        $error =
            "Please enter your username/email and password.";

    } else {

        try {

            $stmt =
                $pdo->prepare("
                    SELECT
                        id,
                        username,
                        email,
                        password_hash,
                        role
                    FROM users
                    WHERE username = ? OR email = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $login,
                $login
            ]);


            $user =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                !$user ||
                !password_verify(
                    $password,
                    $user["password_hash"]
                )
            ) {

                $error =
                    "Invalid username/email or password.";

            } else {

                session_regenerate_id(
                    true
                );


                $_SESSION["user_id"] =
                    $user["id"];


                $_SESSION["username"] =
                    $user["username"];


                $_SESSION["role"] =
                    $user["role"];


                $_SESSION["logged_in"] =
                    true;


                header(
                    "Location: index.php"
                );

                exit;

            }

        } catch (PDOException $e) {

            $error =
                "Something went wrong. Please try again.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log In</title>

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

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f5f5;

            padding-bottom: 60px;

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
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .bottom-bar a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .bottom-bar a:hover {
            text-decoration: underline;
        }

        .container {

            width: 100%;

            max-width: 400px;

            background: white;

            padding: 35px;

            border-radius: 12px;

            box-shadow:
                0 4px 20px rgba(0, 0, 0, .1);

        }

        h1 {

            margin-top: 0;

            margin-bottom: 8px;

        }

        .subtitle {

            color: #666;

            margin-bottom: 25px;

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

        .success {

            background: #dcfce7;

            color: #166534;

            padding: 10px;

            border-radius: 7px;

            margin-bottom: 18px;

        }

        .forgot-wrap {

            text-align: right;

            margin-top: -6px;

            margin-bottom: 18px;

        }

        .forgot-link {

            color: #2563eb;

            text-decoration: none;

            font-size: 14px;

        }

        .forgot-link:hover {

            text-decoration: underline;

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
    </style>

</head>

<body>

    <div class="container">

        <h1>
            Welcome Back
        </h1>

        <div class="subtitle">
            Log in to access your study materials.
        </div>

        <?php if ($success): ?>

            <div class="success">

                <?php

                echo htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>

            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="error">

                <?php

                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <label for="login">

                Username or Email

            </label>


            <input type="text" id="login" name="login" required autocomplete="username" value="<?php

            echo htmlspecialchars(
                $_POST["login"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            );

            ?>">


            <label for="password">

                Password

            </label>


            <input type="password" id="password" name="password" required autocomplete="current-password">


            <div class="forgot-wrap">
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>


            <button type="submit">

                Log In

            </button>


        </form>


        <div class="bottom">

            Don't have an account?


            <a href="signup.php">

                Sign up

            </a>

        </div>


    </div>

    <div class="bottom-bar">
        Any issues or questions? Please email
        <a href="mailto:camchartssupport@gmail.com">camchartssupport@gmail.com</a>
        &nbsp;|&nbsp;
        <a href="privacy.php">Privacy Policy</a>
        &nbsp;|&nbsp;
        <a href="terms.php">Terms of Service</a>
    </div>

</body>

</html>