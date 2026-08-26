<?php

require_once 'db.php';

session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {

        $error = "Please enter your username/email and password.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    username,
                    email,
                    password_hash
                FROM users
                WHERE username = ? OR email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $login,
                $login
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$user ||
                !password_verify(
                    $password,
                    $user["password_hash"]
                )
            ) {

                $error = "Invalid username/email or password.";

            } else {

                session_regenerate_id(true);

                $_SESSION["user_id"] =
                    $user["id"];

                $_SESSION["username"] =
                    $user["username"];

                $_SESSION["logged_in"] =
                    true;

                header("Location: index.php");
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

        <h1>Welcome Back</h1>

        <div class="subtitle">
            Log in to access your study materials.
        </div>

        <?php if ($error): ?>

            <div class="error">

                <?php echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <label for="login">
                Username or Email
            </label>

            <input type="text" id="login" name="login" required autocomplete="username" value="<?php echo htmlspecialchars(
                $_POST["login"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ); ?>"
            >

            <label for="password">
                Password
            </label>

            <input type="password" id="password" name="password" required autocomplete="current-password">

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

</body>

</html>