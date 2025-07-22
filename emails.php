<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit("Bitte einloggen."); }
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Alle E-Mails für den eingeloggten User abrufen
$stmt = $pdo->prepare("SELECT * FROM emails WHERE recipient_id = :user_id ORDER BY sent_at DESC");
$stmt->execute([':user_id' => $user_id]);
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Posteingang</title>
    <link rel="stylesheet" href="desktop.css"> <style>
        /* Spezifische Stile für den Mail-Client */
        body { background-color: #1a1a1a; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .mail-container { display: flex; height: 100vh; }
        .email-list { width: 300px; border-right: 1px solid #333; overflow-y: auto; padding: 10px; }
        .email-item { padding: 10px; border-bottom: 1px solid #2a2a2a; cursor: pointer; }
        .email-item:hover { background-color: #2a2a2a; }
        .email-item.unread { font-weight: bold; }
        .email-display { flex-grow: 1; padding: 20px; }
        #compose-btn { width: 100%; padding: 10px; background-color: #00ff7f; color: #0d0d0d; border: none; cursor: pointer; margin-bottom: 10px; }

        /* Sende-Formular (Modal) */
        #compose-modal { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; }
        #compose-form { background: #2a2a2a; padding: 20px; border-radius: 5px; width: 500px; }
        #compose-form input, #compose-form textarea { width: 100%; padding: 8px; margin-bottom: 10px; background: #1a1a1a; border: 1px solid #333; color: white; box-sizing: border-box; }
        #compose-form button { padding: 10px 15px; border: none; cursor: pointer; }
        #send-mail-btn { background-color: #00ff7f; }
        #cancel-mail-btn { background-color: #ff4d4d; }
    </style>
</head>
<body>

<div class="mail-container">
    <div class="email-list">
        <button id="compose-btn">Neue E-Mail</button>
        <?php foreach ($emails as $email): ?>
            <div class="email-item <?php echo $email['is_read'] ? '' : 'unread'; ?>" data-email-id="<?php echo $email['id']; ?>">
                <div>Von: <?php echo htmlspecialchars($email['sender_name']); ?></div>
                <div>Betreff: <?php echo htmlspecialchars($email['subject']); ?></div>
                <div class="hidden-body" style="display:none;"><?php echo nl2br(htmlspecialchars($email['body_html'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="email-display">
        <h2 id="display-subject">Wähle eine E-Mail aus</h2>
        <p><strong>Von:</strong> <span id="display-sender"></span></p>
        <hr>
        <div id="display-body"></div>
    </div>
</div>

<div id="compose-modal" class="hidden">
    <div id="compose-form">
        <h3>Neue Nachricht</h3>
        <input type="email" id="recipient-email" placeholder="An (z.B. admin@bnd.de)" required>
        <input type="text" id="subject-email" placeholder="Betreff" required>
        <textarea id="body-email" rows="10" placeholder="Deine Nachricht..."></textarea>
        <button id="send-mail-btn">Senden</button>
        <button id="cancel-mail-btn">Abbrechen</button>
        <p id="send-status"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailItems = document.querySelectorAll('.email-item');
    const displaySubject = document.getElementById('display-subject');
    const displaySender = document.getElementById('display-sender');
    const displayBody = document.getElementById('display-body');

    emailItems.forEach(item => {
        item.addEventListener('click', function() {
            displaySubject.textContent = this.querySelector('div:nth-child(2)').textContent.replace('Betreff: ', '');
            displaySender.textContent = this.querySelector('div:nth-child(1)').textContent.replace('Von: ', '');
            displayBody.innerHTML = this.querySelector('.hidden-body').innerHTML;
            this.classList.remove('unread');
            // Hier könnte man eine AJAX-Anfrage senden, um die Mail als gelesen zu markieren
        });
    });

    // Logik für das Sende-Formular
    const composeModal = document.getElementById('compose-modal');
    document.getElementById('compose-btn').addEventListener('click', () => composeModal.classList.remove('hidden'));
    document.getElementById('cancel-mail-btn').addEventListener('click', () => composeModal.classList.add('hidden'));

    document.getElementById('send-mail-btn').addEventListener('click', async function() {
        const recipient = document.getElementById('recipient-email').value;
        const subject = document.getElementById('subject-email').value;
        const body = document.getElementById('body-email').value;
        const statusEl = document.getElementById('send-status');

        if (!recipient || !subject) {
            statusEl.textContent = "Empfänger und Betreff sind erforderlich.";
            return;
        }

        const formData = new FormData();
        formData.append('to', recipient);
        formData.append('subject', subject);
        formData.append('body', body);

        try {
            const response = await fetch('api_send_email.php', { method: 'POST', body: formData });
            const result = await response.json();
            statusEl.textContent = result.message;
            if (result.success) {
                setTimeout(() => {
                    composeModal.classList.add('hidden');
                    // Optional: Posteingang neu laden
                }, 2000);
            }
        } catch (error) {
            statusEl.textContent = "Ein Fehler ist aufgetreten.";
        }
    });
});
</script>

</body>
</html>