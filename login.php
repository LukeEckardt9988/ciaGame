<?php
session_start();
require 'db_connect.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = 'Beide Felder sind erforderlich.';
    } else {
        $sql = "SELECT id, username, password_hash FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Passwort ist korrekt, Session starten
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $message = 'Falscher Codename oder falsches Passwort.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>CIA Game - Login</title>
     <style>
        body { font-family: sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input { display: block; width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 0.75rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { margin-bottom: 1rem; color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Rekruten-Login</h2>
        <?php if (!empty($message)) { echo "<p class='error'>" . htmlspecialchars($message) . "</p>"; } ?>
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="Codename" required>
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Einloggen</button>
        </form>
        <p>Noch kein Rekrut? <a href="register.php">Hier registrieren</a></p>
    </div>
</body>
</html>