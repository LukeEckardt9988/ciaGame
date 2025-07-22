<?php
require 'db_connect.php';
$message = '';

// --- Logik zum Finden freier IPs ---
$MIN_IP_OCTET = 2;
$MAX_IP_OCTET = 254;
$SUBNET = "10.0.10.";

// 1. Hole alle belegten IPs
$stmt = $pdo->query("SELECT ip_address FROM users WHERE ip_address IS NOT NULL");
$occupied_ips = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 2. Erstelle eine Liste aller möglichen IPs und filtere die belegten heraus
$available_ips = [];
for ($i = $MIN_IP_OCTET; $i <= $MAX_IP_OCTET; $i++) {
    $current_ip = $SUBNET . $i;
    if (!in_array($current_ip, $occupied_ips)) {
        $available_ips[] = $current_ip;
    }
}


// --- Formularverarbeitung ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $chosen_ip = trim($_POST['ip_address']);

    // Serverseitige Validierung, ob die IP wirklich frei war
    if (empty($username) || empty($email) || empty($password) || empty($chosen_ip) || !in_array($chosen_ip, $available_ips)) {
        $message = 'Alle Felder sind erforderlich und die IP muss verfügbar sein.';
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Starte eine Transaktion, um Datenintegrität zu sichern
            $pdo->beginTransaction();

            // 1. User erstellen
            $sql = "INSERT INTO users (username, email, password_hash, ip_address) VALUES (:username, :email, :password_hash, :ip)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':ip' => $chosen_ip
            ]);
            $new_user_id = $pdo->lastInsertId();

            // 2. Zugehöriges Gerät für den User erstellen
            $sql_device = "INSERT INTO player_devices (user_id, hostname) VALUES (:user_id, :hostname)";
            $stmt_device = $pdo->prepare($sql_device);
            $stmt_device->execute([
                ':user_id' => $new_user_id,
                ':hostname' => strtolower($username) . '-pc' // z.B. 'luke-pc'
            ]);
            $new_device_id = $pdo->lastInsertId();

            // 3. User-Tabelle mit der neuen Device-ID aktualisieren
            $sql_update = "UPDATE users SET device_id = :device_id WHERE id = :user_id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([':device_id' => $new_device_id, ':user_id' => $new_user_id]);

            $pdo->commit();
            $message = 'Registrierung erfolgreich! Du kannst dich jetzt einloggen.';

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $message = 'Fehler: Benutzername, E-Mail oder IP bereits vergeben.';
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
    <title>BND Game - Registrierung</title>
    <link rel="stylesheet" href="login_style.css"> 
</head>
<body>
    <div class="container">
        <h2>Neuen Rekruten anwerben</h2>
        <?php if (!empty($message)) { echo "<p class='message'>" . htmlspecialchars($message) . "</p>"; } ?>
        <form action="register.php" method="post">
            <input type="text" name="username" placeholder="Codename (Username)" required>
            <input type="email" name="email" placeholder="Sichere E-Mail" required>
            <input type="password" name="password" placeholder="Passwort" required>
            
            <label for="ip_address">Wähle deine System-IP:</label>
            <select name="ip_address" id="ip_address" required>
                <?php if (empty($available_ips)): ?>
                    <option value="">-- Kein freier Slot im Netzwerk --</option>
                <?php else: ?>
                    <?php foreach ($available_ips as $ip): ?>
                        <option value="<?php echo htmlspecialchars($ip); ?>"><?php echo htmlspecialchars($ip); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <button type="submit">Registrierung abschließen</button>
        </form>
        <p>Schon dabei? <a href="login.php">Hier einloggen</a></p>
    </div>
</body>
</html>