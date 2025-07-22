<?php
require 'db_connect.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email) || empty($password)) {
        $message = 'Alle Felder sind erforderlich.';
    } else {
        // Passwort sicher hashen
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $message = 'Registrierung erfolgreich! Du kannst dich jetzt einloggen.';
            }
        } catch (PDOException $e) {
            // Fehler abfangen, falls Username oder E-Mail schon existieren
            if ($e->errorInfo[1] == 1062) {
                $message = 'Fehler: Benutzername oder E-Mail bereits vergeben.';
            } else {
                $message = 'Ein Fehler ist aufgetreten: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>CIA Game - Registrierung</title>
    <style>
        body { font-family: sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input { display: block; width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 0.75rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .message { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Neuen Rekruten anwerben</h2>
        <?php if (!empty($message)) { echo "<p class='message'>" . htmlspecialchars($message) . "</p>"; } ?>
        <form action="register.php" method="post">
            <input type="text" name="username" placeholder="Codename (Username)" required>
            <input type="email" name="email" placeholder="Sichere E-Mail" required>
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Registrieren</button>
        </form>
        <p>Schon dabei? <a href="login.php">Hier einloggen</a></p>
    </div>
</body>
</html>