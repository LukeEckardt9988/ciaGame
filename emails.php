<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Logik zum Markieren einer E-Mail als gelesen
if (isset($_POST['mark_as_read']) && isset($_POST['email_id'])) {
    $email_id = $_POST['email_id'];
    try {
        // Hier sollte man eine separate Tabelle für User-E-Mail-Status haben,
        // falls E-Mails für mehrere User gedacht sind und jeder seinen eigenen Lesestatus hat.
        // Für dieses Beispiel gehen wir davon aus, dass der Lesestatus global für die E-Mail ist,
        // oder Sie erweitern dies später mit einer user_emails Tabelle (user_id, email_id, is_read).
        // Für den Moment aktualisieren wir direkt die E-Mail-Tabelle.
        $stmt = $pdo->prepare("UPDATE emails SET is_read = TRUE WHERE id = :email_id");
        $stmt->bindParam(':email_id', $email_id, PDO::PARAM_INT);
        $stmt->execute();
        // Keine weitere Ausgabe, da dies ein AJAX-Aufruf sein wird
        exit;
    } catch (PDOException $e) {
        // Fehlerbehandlung, optional loggen
        error_log("Fehler beim Markieren der E-Mail als gelesen: " . $e->getMessage());
    }
}


// E-Mails aus der Datenbank laden
try {
    // Wählen Sie die neue Spalte 'is_read' aus
    $stmt = $pdo->query("SELECT id, sender_name, sender_email, subject, body_html, is_phishing, phishing_analysis_data, is_read FROM emails ORDER BY sent_at DESC");
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $emails = [];
    error_log("Datenbankfehler beim Laden der E-Mails: " . $e->getMessage());
}

// Holen des Benutzernamens für die E-Mail-Personalisierung
$username_for_email = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Posteingang</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="level1.css"> <link rel="stylesheet" href="level2.css"> </head>

<body>
    <div class="header">
        <h1>Kommunikationszentrale: Posteingang</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Ausloggen</a>
        </div>
    </div>

    <div class="main-content-wrapper"> 
        <div class="level2-main-content">
            <div class="panel email-list">
                <h2>Posteingang</h2>
                <div id="email-inbox">
                    <?php if (empty($emails)): ?>
                        <p>Keine E-Mails vorhanden.</p>
                    <?php else: ?>
                        <?php foreach ($emails as $email): ?>
                            <div class="email-item <?php echo $email['is_read'] ? '' : 'unread'; ?>" data-email-id="<?php echo $email['id']; ?>">
                                <span class="email-subject"><?php echo htmlspecialchars($email['subject']); ?></span><br>
                                <span class="email-sender">Von: <?php echo htmlspecialchars($email['sender_name']); ?> &lt;<?php echo htmlspecialchars($email['sender_email']); ?>&gt;</span>
                                <span class="hidden-email-content" 
                                    data-is-phishing="<?php echo $email['is_phishing'] ? 'true' : 'false'; ?>" 
                                    <?php if ($email['is_phishing']): ?>
                                    data-phishing-analysis='<?php echo htmlspecialchars($email['phishing_analysis_data'], ENT_QUOTES, 'UTF-8'); ?>'
                                    <?php endif; ?>
                                >
                                    <?php 
                                    // Hier ersetzen wir den Platzhalter mit dem tatsächlichen Benutzernamen
                                    echo str_replace('[USERNAME]', $username_for_email, $email['body_html']);
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel email-content-display">
                <h2>E-Mail Inhalt</h2>
                <div id="email-display-area" class="email-full-content">
                    Wählen Sie eine E-Mail aus, um den Inhalt anzuzeigen.
                </div>
            </div>
        </div>
    </div>

    <div id="phishing-analysis-modal-overlay" class="hidden">
        <div id="phishing-analysis-modal-content">
            <h2>Phishing-Analyse-Bericht</h2>

            <div class="analysis-section">
                <div class="analysis-panel">
                    <h3>E-Mail-Header & Herkunft</h3>
                    <p><strong>Von:</strong> <span id="modal-sender-display"></span></p>
                    <p><strong>Betreff:</strong> <span id="modal-subject-display"></span></p>
                    <p><strong>Original IP:</strong> <span id="modal-origin-ip" class="highlight-ip" title="Klicken für Whois-Info"></span></p>
                    <div id="modal-whois-info">
                        <p class="red-flag">Klicken Sie auf die IP-Adresse für Whois-Informationen.</p>
                    </div>
                    <p><strong>Header-Snippet:</strong> <span id="modal-header-snippet"></span></p>
                    <p class="red-flag">Die Ursprungs-IP-Adresse verweist auf einen Server in Russland. Die Domain `.ru` ist ebenfalls ein Warnsignal!</p>
                </div>

                <div class="analysis-panel">
                    <h3>Analyse der Bedrohung</h3>
                    <p><strong>Verdächtiger Link/Aktion:</strong> <a id="modal-phishing-link" href="#" target="_blank" class="red-flag"></a></p>
                    <p class="info-text">Dieser Link führt zu einer externen, nicht vertrauenswürdigen Domäne. Der Inhalt soll den Benutzer zum Download einer "strengen Direktive" verleiten.</p>
                    <p><strong>Simulierter SHA256-Hash der Datei:</strong> <span id="modal-sha256-hash" class="highlight-sha"></span></p>
                    <p class="info-text">Ein Hash wie dieser (realistisch: viel komplexer als dieser Platzhalter) wäre der digitale Fingerabdruck der heruntergeladenen Datei. Er kann auf bekannte Malware hinweisen, wie z.B. Spionagesoftware, die einen SSL-Tunnel zum Abfangen von Daten öffnet.</p>
                    <p class="red-flag"><strong>Gefahr:</strong> Potenzielle Spionagesoftware oder Ransomware.</p>
                </div>
            </div>

            <p class="info-text">
                **Merke:** Achten Sie immer auf Absenderadressen, Linkziele und unerwartete Dateianhänge. Hovern Sie über Links, um das tatsächliche Ziel zu sehen, bevor Sie klicken. Echte Regierungsdokumente werden niemals auf diese Weise verteilt.
            </p>

            <button id="modal-phishing-close-btn" class="modal-close-btn">Analyse schließen</button>
        </div>
    </div>

    <script>
        const emailInbox = document.getElementById('email-inbox');
        const emailDisplayArea = document.getElementById('email-display-area');

        // Modal-Elemente (Phishing Analyse Modal)
        const phishingAnalysisModalOverlay = document.getElementById('phishing-analysis-modal-overlay');
        const modalPhishingCloseBtn = document.getElementById('modal-phishing-close-btn');
        const modalSenderDisplay = document.getElementById('modal-sender-display');
        const modalSubjectDisplay = document.getElementById('modal-subject-display');
        const modalOriginIp = document.getElementById('modal-origin-ip');
        const modalWhoisInfo = document.getElementById('modal-whois-info');
        const modalHeaderSnippet = document.getElementById('modal-header-snippet');
        const modalPhishingLink = document.getElementById('modal-phishing-link');
        const modalSha256Hash = document.getElementById('modal-sha256-hash');

        // Funktion zum Markieren einer E-Mail als gelesen in der Datenbank
        function markEmailAsRead(emailId) {
            fetch('emails.php', { // AJAX-Aufruf an dieselbe Seite
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_as_read=true&email_id=${emailId}`
            })
            .then(response => {
                if (!response.ok) {
                    console.error('Fehler beim Markieren als gelesen:', response.statusText);
                }
            })
            .catch(error => console.error('Netzwerkfehler beim Markieren als gelesen:', error));
        }

        // Event Handler für Klicks auf E-Mails in der Liste
        emailInbox.addEventListener('click', function(e) {
            let emailItem = e.target.closest('.email-item');
            if (emailItem) {
                // Entfernt "selected" Klasse von allen anderen E-Mails
                document.querySelectorAll('.email-item').forEach(item => item.classList.remove('selected'));
                // Fügt "selected" Klasse zur geklickten E-Mail hinzu
                emailItem.classList.add('selected');

                const emailId = emailItem.dataset.emailId;
                const emailContentDiv = emailItem.querySelector('.hidden-email-content');
                const emailSubject = emailItem.querySelector('.email-subject').textContent;
                const emailSender = emailItem.querySelector('.email-sender').textContent;
                const emailBodyHtml = emailContentDiv.innerHTML; // Holt den HTML-Inhalt
                const isPhishing = emailContentDiv.dataset.isPhishing === 'true';

                emailDisplayArea.innerHTML = `
                    <div class="email-header">
                        <span class="email-subject">${emailSubject}</span><br>
                        <span class="email-sender">${emailSender}</span>
                    </div>
                    <hr style="border-color:#444;">
                    <p>${emailBodyHtml}</p>
                `;
                
                // Markiert die E-Mail als gelesen (visuell und in DB)
                if (emailItem.classList.contains('unread')) {
                    emailItem.classList.remove('unread');
                    markEmailAsRead(emailId);
                }

                // Wichtig: Event Listener für Links im E-Mail-Inhalt neu zuweisen
                const linksInEmail = emailDisplayArea.querySelectorAll('a');
                linksInEmail.forEach(link => {
                    // Prüfen, ob es sich um den speziellen Phishing-Link handelt
                    if (link.classList.contains('phishing-link')) {
                        link.addEventListener('click', handlePhishingLinkClick);
                    } else {
                        // Standard-Links wie das BND-Portal einfach in neuem Tab öffnen
                        link.setAttribute('target', '_blank');
                    }
                });

            } else {
                emailDisplayArea.innerHTML = "Wählen Sie eine E-Mail aus, um den Inhalt anzuzeigen.";
            }
        });

        // Handler für Klicks auf den Phishing-Link (öffnet das Analyse-Modal)
        function handlePhishingLinkClick(e) {
            e.preventDefault(); // Verhindert das Standard-Link-Verhalten (Navigation)
            
            const selectedEmailItem = document.querySelector('.email-item.selected');
            if (!selectedEmailItem) {
                alert("Bitte wählen Sie zuerst die Phishing-E-Mail aus.");
                return;
            }

            const emailContentDiv = selectedEmailItem.querySelector('.hidden-email-content');
            const isPhishing = emailContentDiv.dataset.isPhishing === 'true';
            
            if (isPhishing) {
                try {
                    const phishingAnalysisData = JSON.parse(emailContentDiv.dataset.phishingAnalysis);
                    const emailSubject = selectedEmailItem.querySelector('.email-subject').textContent;
                    const emailSender = selectedEmailItem.querySelector('.email-sender').textContent;

                    // Füllt das Modal mit Analyse-Daten
                    modalSenderDisplay.textContent = emailSender;
                    modalSubjectDisplay.textContent = emailSubject;
                    modalOriginIp.textContent = phishingAnalysisData.ip_address;
                    modalOriginIp.dataset.whoisIp = phishingAnalysisData.ip_address; // Speichert die IP für Whois-Klick
                    modalWhoisInfo.innerHTML = `<p class="red-flag">Klicken Sie auf die IP-Adresse für Whois-Informationen.</p>`; // Initialer Hinweis
                    modalHeaderSnippet.textContent = phishingAnalysisData.header_snippet;
                    
                    // Zeigt den tatsächlichen Link, der geklickt wurde, oder den simulierten Link
                    modalPhishingLink.href = e.target.href || phishingAnalysisData.simulated_phishing_url;
                    modalPhishingLink.textContent = e.target.textContent + " (" + (e.target.href || phishingAnalysisData.simulated_phishing_url) + ")"; // Text des Links + URL
                    
                    modalSha256Hash.textContent = phishingAnalysisData.sha256_hash;

                    phishingAnalysisModalOverlay.classList.remove('hidden'); // Modal anzeigen
                } catch (error) {
                    console.error("Fehler beim Parsen der Phishing-Analyse-Daten:", error);
                    alert("Fehler bei der Analyse dieser E-Mail.");
                }
            } else {
                // Diese Meldung wird nur kommen, wenn man den Link in einer Nicht-Phishing-Email klickt
                // Was aufgrund der HTML-Struktur der "phishing-link"-Klasse nicht vorkommen sollte
                alert("Dies ist keine Phishing-E-Mail. Keine Analyse verfügbar.");
            }
        }

        // Event Listener für den Klick auf die IP-Adresse im Modal (simulierte Whois)
        modalOriginIp.addEventListener('click', function() {
            const selectedEmailItem = document.querySelector('.email-item.selected');
            if (!selectedEmailItem) return;

            const emailContentDiv = selectedEmailItem.querySelector('.hidden-email-content');
            const phishingAnalysisData = JSON.parse(emailContentDiv.dataset.phishingAnalysis);
            
            if (phishingAnalysisData && phishingAnalysisData.whois_full_output) {
                modalWhoisInfo.innerHTML = phishingAnalysisData.whois_full_output; // HTML-Inhalt direkt einfügen
            } else {
                modalWhoisInfo.innerHTML = `<p class="red-flag">Keine Whois-Informationen verfügbar.</p>`;
            }
        });


        // Event Listener für den Schließen-Button im Phishing-Analyse-Modal
        modalPhishingCloseBtn.addEventListener('click', function() {
            phishingAnalysisModalOverlay.classList.add('hidden'); // Modal verstecken
        });

        // Event Listener zum Schließen des Modals bei Klick außerhalb des Inhalts
        phishingAnalysisModalOverlay.addEventListener('click', function(e) {
            if (e.target === phishingAnalysisModalOverlay) {
                phishingAnalysisModalOverlay.classList.add('hidden');
            }
        });

    </script>
</body>

</html>