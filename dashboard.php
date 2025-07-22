<?php
session_start();

// Wenn der User nicht eingeloggt ist, zum Login weiterleiten
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db_connect.php';

// Hole alle Achievements des eingeloggten Benutzers
$sql = "SELECT a.name, a.description, a.icon
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; background-color: #1a1a1a; color: #e0e0e0; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #444; padding-bottom: 1rem; margin-bottom: 20px;}
        .header a {
            color: #00ff7f; /* Passend zum CI des Spiels */
            text-decoration: none;
            background-color: #2a2a2a;
            padding: 8px 15px;
            border-radius: 4px;
            border: 1px solid #444;
        }
        .header a:hover {
            background-color: #3a3a3a;
            text-decoration: none;
        }
        .sections { display: flex; gap: 40px; }
        .section { flex: 1; background: #2a2a2a; padding: 1.5rem; border-radius: 8px; border: 1px solid #333; }
        .section h2 { color: #00ff7f; margin-top: 0; }
        .section ul { list-style: none; padding: 0; }
        .section li { margin-bottom: 10px; }
        .section a { color: #57e0ff; text-decoration: none; }
        .section a:hover { text-decoration: underline; }
        .achievement-card { background: #1a1a1a; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 3px solid #f0e68c; }
        .achievement-card h3 { color: #f0e68c; margin-top: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Willkommen, Rekrut <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <a href="logout.php">Ausloggen</a>
    </div>

    <div class="sections">
        <div class="section email-access">
            <h2>Kommunikationszentrale</h2>
            <ul>
                <li><a href="emails.php">E-Mail-Posteingang &raquo;</a></li>
            </ul>
        </div>

        <div class="section achievements">
            <h2>Deine Auszeichnungen</h2>
            <?php if (empty($achievements)): ?>
                <p>Du hast noch keine Auszeichnungen erhalten. Schließe Missionen ab, um sie zu verdienen.</p>
            <?php else: ?>
                <?php foreach ($achievements as $ach): ?>
                    <div class="achievement-card">
                        <h3><?php echo htmlspecialchars($ach['name']); ?></h3>
                        <p><?php echo htmlspecialchars($ach['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>