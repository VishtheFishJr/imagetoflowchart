<?php

require_once 'db.php';

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"])
) {
    header("Location: login.php");
    exit;
}

$roleStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE id = ?
    LIMIT 1
");
$roleStmt->execute([$_SESSION["user_id"]]);
$currentRole = $roleStmt->fetchColumn();

if ($currentRole !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $deleteId = (int)($_POST["delete_id"] ?? 0);

    if ($deleteId <= 0) {
        $error = "Invalid user.";
    } elseif ($deleteId === (int)$_SESSION["user_id"]) {
        $error = "You cannot delete your own account while logged in.";
    } else {
        try {
            $pdo->beginTransaction();

            $deleteItems = $pdo->prepare("DELETE FROM generated_items WHERE user_id = ?");
            $deleteItems->execute([$deleteId]);

            $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $deleteUser->execute([$deleteId]);

            $pdo->commit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Could not delete that user.";
        }
    }
}

try {
    $stmt = $pdo->query("
        SELECT id, username, email, role, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = "Could not load users.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f5f5; color: #111; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 20px; }
        h1 { margin: 0; }
        .back { text-decoration: none; color: #2563eb; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 7px; margin-bottom: 18px; }
        .table-wrap { background: #fff; border: 1px solid #ddd; border-radius: 10px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        tr:last-child td { border-bottom: none; }
        .delete-button { border: none; border-radius: 6px; padding: 8px 12px; background: #dc2626; color: white; cursor: pointer; }
        .delete-button:hover { background: #b91c1c; }
        .role { font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="top">
            <h1>Users</h1>
            <a class="back" href="index.php">← Back to Scanner</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td><?php echo htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td class="role"><?php echo htmlspecialchars($user["role"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td><?php echo htmlspecialchars($user["created_at"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
                            <td>
                                <?php if ((int)$user["id"] !== (int)$_SESSION["user_id"]): ?>
                                    <form method="POST" onsubmit="return confirm('Delete this user and their generated files?');">
                                        <input type="hidden" name="delete_id" value="<?php echo (int)$user["id"]; ?>">
                                        <button class="delete-button" type="submit">Delete</button>
                                    </form>
                                <?php else: ?>
                                    Current account
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
