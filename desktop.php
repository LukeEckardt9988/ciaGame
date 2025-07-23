<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>BND Desktop</title>
    <link rel="stylesheet" href="desktop.css">
</head>

<body>
    <div id="desktop">
        <div class="desktop-icon" id="icon-console">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/console.png" alt="console" />
            <span>Konsole</span>
        </div>
        <div class="desktop-icon" id="icon-emails">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/new-post.png" alt="emails" />
            <span>E-Mails</span>
        </div>
        <div class="desktop-icon" id="icon-browser">
            <img src="https://img.icons8.com/ios-filled/100/00ff7f/internet.png" alt="browser" />
            <span>Browser</span>
        </div>

        <div id="console-window" class="window-container hidden">
            <div class="window-header"><span>BND Secure Terminal</span><button id="close-console-btn" class="win-btn">X</button></div>
            <div class="window-content">
                <div id="console-output" class="console-output-area"></div>
                <div class="console-input-line"><span class="prompt">></span><input type="text" id="console-input" class="console-input"></div>
            </div>
            <div class="resizer bottom-right"></div>
        </div>

        <div id="emails-window" class="window-container hidden">
            <div class="window-header"><span>Posteingang</span><button id="close-emails-btn" class="win-btn">X</button></div>
            <div class="window-content" style="padding:0;"><iframe src="emails.php" style="width:100%; height:100%; border:none;"></iframe></div>
            <div class="resizer bottom-right"></div>
        </div>

        <div id="browser-window" class="window-container hidden" style="width: 900px; height: 650px;">
            <div class="window-header"><span>BND Intranet Browser</span><button id="close-browser-btn" class="win-btn">X</button></div>
            <div class="window-content" style="padding:0;">
                <iframe src="browser.php?page=start.html" style="width:100%; height:100%; border:none;"></iframe>
            </div>
            <div class="resizer bottom-right"></div>
        </div>
    </div>

    <div id="taskbar">
        <a href="logout.php" class="logout-link">Ausloggen</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let highestZ = 101;

            function bringToFront(element) {
                highestZ++;
                element.style.zIndex = highestZ;
            }

            function makeDraggable(element) {
                const header = element.querySelector('.window-header');
                let pos1 = 0,
                    pos2 = 0,
                    pos3 = 0,
                    pos4 = 0;
                header.onmousedown = function(e) {
                    e.preventDefault();
                    bringToFront(element);
                    pos3 = e.clientX;
                    pos4 = e.clientY;
                    document.onmouseup = closeDragElement;
                    document.onmousemove = elementDrag;
                };

                function elementDrag(e) {
                    e.preventDefault();
                    pos1 = pos3 - e.clientX;
                    pos2 = pos4 - e.clientY;
                    pos3 = e.clientX;
                    pos4 = e.clientY;
                    element.style.top = (element.offsetTop - pos2) + "px";
                    element.style.left = (element.offsetLeft - pos1) + "px";
                }

                function closeDragElement() {
                    document.onmouseup = null;
                    document.onmousemove = null;
                }
            }

            function makeResizable(element) {
                const resizer = element.querySelector('.resizer.bottom-right');
                if (!resizer) return;
                const minimum_size = 200;
                let original_width = 0,
                    original_height = 0;
                let original_mouse_x = 0,
                    original_mouse_y = 0;

                resizer.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    original_width = parseFloat(getComputedStyle(element, null).getPropertyValue('width').replace('px', ''));
                    original_height = parseFloat(getComputedStyle(element, null).getPropertyValue('height').replace('px', ''));
                    original_mouse_x = e.pageX;
                    original_mouse_y = e.pageY;
                    window.addEventListener('mousemove', resize);
                    window.addEventListener('mouseup', stopResize);
                });

                function resize(e) {
                    const width = original_width + (e.pageX - original_mouse_x);
                    const height = original_height + (e.pageY - original_mouse_y);
                    if (width > minimum_size) {
                        element.style.width = width + 'px';
                    }
                    if (height > minimum_size) {
                        element.style.height = height + 'px';
                    }
                }

                function stopResize() {
                    window.removeEventListener('mousemove', resize);
                }
            }

            // --- Initialisierung für die Konsole ---
            const consoleIcon = document.getElementById('icon-console');
            const consoleWindow = document.getElementById('console-window');
            const closeConsoleBtn = document.getElementById('close-console-btn');

            consoleIcon.addEventListener('click', () => {
                consoleWindow.classList.remove('hidden');
                bringToFront(consoleWindow);
            });
            closeConsoleBtn.addEventListener('click', () => consoleWindow.classList.add('hidden'));
            makeDraggable(consoleWindow);
            makeResizable(consoleWindow);

            // --- Initialisierung für E-Mails ---
            const emailsIcon = document.getElementById('icon-emails');
            const emailsWindow = document.getElementById('emails-window');
            const closeEmailsBtn = document.getElementById('close-emails-btn');

            emailsIcon.addEventListener('click', () => {
                emailsWindow.classList.remove('hidden');
                bringToFront(emailsWindow);
            });
            closeEmailsBtn.addEventListener('click', () => emailsWindow.classList.add('hidden'));
            makeDraggable(emailsWindow);
            makeResizable(emailsWindow);

            // --- KORRIGIERT: Initialisierung für den Browser ---
            const browserIcon = document.getElementById('icon-browser');
            const browserWindow = document.getElementById('browser-window');
            const closeBrowserBtn = document.getElementById('close-browser-btn');

            browserIcon.addEventListener('click', () => {
                browserWindow.classList.remove('hidden');
                bringToFront(browserWindow);
            });
            closeBrowserBtn.addEventListener('click', () => browserWindow.classList.add('hidden'));
            makeDraggable(browserWindow);
            makeResizable(browserWindow);

            // --- Konsolen-Logik (unverändert) ---
            const consoleInput = document.getElementById('console-input');
            const consoleOutput = document.getElementById('console-output');

            consoleInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const command = consoleInput.value;
                    if (command.trim() === '') return;
                    consoleOutput.innerHTML += `<br><span style="color: #f0e68c;">> ${command}</span><br>`;
                    consoleInput.value = '';
                    if (command.toLowerCase() === 'clear') {
                        consoleOutput.innerHTML = '';
                        return;
                    }
                    fetch('api_console.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'command=' + encodeURIComponent(command)
                        })
                        .then(response => response.json())
                        .then(data => {
                            const formattedOutput = data.output.replace(/\n/g, '<br>');
                            consoleOutput.innerHTML += `<span style="color: #00ff7f;">${formattedOutput}</span>`;
                            consoleOutput.scrollTop = consoleOutput.scrollHeight;
                        })
                        .catch(error => {
                            consoleOutput.innerHTML += '<br>FATAL: Verbindung zum Command-Server verloren.';
                            consoleOutput.scrollTop = consoleOutput.scrollHeight;
                        });
                }
            });
        });
    </script>
</body>

</html>