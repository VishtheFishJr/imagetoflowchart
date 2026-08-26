<?php

require_once 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "user";

    if (
        $username === "" ||
        $email === "" ||
        $password === ""
    ) {

        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($username) < 3) {

        $error = "Username must be at least 3 characters.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } elseif (
        $role !== "user" &&
        $role !== "admin"
    ) {

        $error = "Invalid account role.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ? OR email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username,
                $email
            ]);

            if ($stmt->fetch()) {

                $error = "That username or email is already in use.";

            } else {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (
                        username,
                        email,
                        password_hash,
                        role
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $username,
                    $email,
                    $passwordHash,
                    $role
                ]);

                $userId = $pdo->lastInsertId();

                session_regenerate_id(true);

                $_SESSION["user_id"] = $userId;
                $_SESSION["username"] = $username;
                $_SESSION["logged_in"] = true;
                $_SESSION["role"] = $role;

                header("Location: index.php");
                exit;
            }

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

    <title>Sign Up</title>

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

        input,
        select {

            width: 100%;

            padding: 12px;

            margin-bottom: 16px;

            border: 1px solid #ccc;

            border-radius: 7px;

            font-size: 15px;

            background: white;

        }

        input:focus,
        select:focus {

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

        <h1>Create Account</h1>

        <div class="subtitle">
            Create an account to save your study materials.
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

            <label for="username">
                Username
            </label>

            <input type="text" id="username" name="username" required autocomplete="username" value="<?php echo htmlspecialchars(
                $_POST["username"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ); ?>">

            <label for="email">
                Email
            </label>

            <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo htmlspecialchars(
                $_POST["email"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ); ?>">

            <label for="password">
                Password
            </label>

            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                autocomplete="new-password">

            <label for="role">
                Account Type
            </label>

            <select id="role" name="role" required>

                <option value="user" <?php echo (
                    ($_POST["role"] ?? "user") === "user"
                ) ? "selected" : ""; ?>> Regular
                    User </option>

                <option value="admin" <?php echo (
                    ($_POST["role"] ?? "") === "admin"
                ) ? "selected" : ""; ?>> Admin
                </option>

            </select>

            <button type="submit">
                Create Account
            </button>

        </form>

        <div class="bottom">

            Already have an account?

            <a href="login.php">
                Log in
            </a>

        </div>

    </div>

</body>

</html>