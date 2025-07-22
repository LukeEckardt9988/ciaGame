<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php';

// Diese Abfrage bleibt, falls du sie später für etwas anderes brauchst.
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
    <title>BND Desktop</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="desktop.css">
</head>

<body>

    <div id="desktop">
        <div class="desktop-icon" id="icon-console">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/console.png" alt="console" />
            <span>Konsole</span>
        </div>
        <div class="desktop-icon" id="icon-emails">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/new-post.png" alt="new-post" />
            <span>E-Mails</span>
        </div>
    </div>

    <div id="taskbar">
        <div class="start-menu-button">BND</div>
        <div id="taskbar-windows"></div>
        <div class="taskbar-logout">
            <a href="logout.php">Ausloggen</a>
        </div>
    </div>

    <div class="window-template" id="console-window-template">
        <div class="window-container resizable-draggable" data-window-id="console">
            <div class="window-header">
                <span class="window-title">C:\WINDOWS\system32\cmd.exe</span>
                <div class="window-buttons">
                    <button class="win-btn win-minimize">-</button>
                    <button class="win-btn win-maximize">[]</button>
                    <button class="win-btn win-close">X</button>
                </div>
            </div>
            <div class="window-content">
                <div class="console-parent-container" style="height:100%; display:flex; flex-direction:column;">
                    <div class="panel console-container" style="flex-grow:1;">
                        <div id="console-output">BND Secure Terminal v3.0<br>Bereit...<br></div>
                    </div>
                    <div class="console-input-area">
                        <select id="command-dropdown" disabled>
                            <option>-- Programm wählen --</option>
                        </select>
                        <input type="text" id="argument-input" placeholder="---" disabled>
                        <button id="execute-btn" disabled>Execute</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="window-template" id="console-window-template">
        <div class="window-container resizable-draggable" data-window-id="console">
            <div class="window-header">
                <span class="window-title">BND Secure Terminal</span>
                <div class="window-buttons">
                    <button class="win-btn win-minimize">-</button>
                    <button class="win-btn win-maximize">[]</button>
                    <button class="win-btn win-close">X</button>
                </div>
            </div>
            <div class="window-content" style="background-color: #0d0d0d; color: #00ff7f; font-family: 'Courier New', Courier, monospace;">
                <div id="console-output-area" style="height: calc(100% - 30px); overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">
                    BND Secure Terminal v3.0 gestartet.<br>
                    Nutze 'help' für eine Liste verfügbarer Programme.<br>
                </div>
                <div class="console-input-line" style="display: flex; height: 30px;">
                    <span class="prompt" style="color: #f0e68c;">></span>
                    <input type="text" id="console-input" style="flex-grow: 1; background: transparent; border: none; color: #00ff7f; outline: none; padding-left: 5px;">
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="desktop.js"></script>
</body>

</html>