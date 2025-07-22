<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    echo json_encode(['output' => 'Fehler: Nicht autorisierter Zugriff.']);
    exit;
}

require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$full_command = trim($_POST['command']);
$parts = explode(' ', $full_command);
$program = strtolower(array_shift($parts)); // Das erste Wort ist das Programm, z.B. 'nmap'
$arguments = $parts; // Der Rest sind Argumente

$response = ['output' => "Befehl nicht gefunden: '$program'. Tippe 'help' für eine Liste."];

// Funktion zur Simulation eines Nmap-Scans
function simulate_nmap($target_ip, $pdo) {
    // 1. Finde das Zielgerät in der Datenbank
    $stmt = $pdo->prepare("SELECT u.username, d.* FROM player_devices d JOIN users u ON d.user_id = u.id WHERE u.ip_address = :ip");
    $stmt->execute([':ip' => $target_ip]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        return "Host $target_ip scheint down zu sein oder existiert nicht.";
    }

    $output = "Starte Nmap Scan für $target_ip ($device[hostname])...\n";
    $output .= "Scan Report für $target_ip\n";
    $output .= "PORT    STATE    SERVICE\n";

    $ports_data = json_decode($device['ports'], true);
    $found_open_port = false;

    foreach ($ports_data as $port => $details) {
        if ($details['status'] === 'open') {
            $found_open_port = true;
            $service = htmlspecialchars($details['service'] ?? 'unknown');
            $output .= str_pad($port."/tcp", 8) . str_pad("open", 9) . "$service\n";
        }
    }

    if (!$found_open_port) {
        $output .= "Alle 1000 gescannten Ports auf $target_ip sind filtered.";
    }
    
    // Protokolliere den Scan im Event-Log des Ziels
    // TODO: Füge hier die Logik zum Schreiben in $device['event_log'] hinzu

    return $output;
}


// Befehls-Router
switch ($program) {
    case 'help':
        $response['output'] = "Verfügbare Programme:\n  nmap   - Netzwerk-Scanner\n  whois  - Zeigt Informationen zu einer IP\n  clear  - Leert die Konsole (nur visuell)";
        break;

    case 'nmap':
        $target_ip = end($arguments); // Annahme: die IP ist das letzte Argument
        if (filter_var($target_ip, FILTER_VALIDATE_IP)) {
            $response['output'] = simulate_nmap($target_ip, $pdo);
        } else {
            $response['output'] = "Nmap Fehler: Ungültige oder fehlende Ziel-IP.";
        }
        break;

    case 'whois':
        $target_ip = $arguments[0] ?? '';
        if (filter_var($target_ip, FILTER_VALIDATE_IP)) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE ip_address = :ip");
            $stmt->execute([':ip' => $target_ip]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $response['output'] = "IP $target_ip ist registriert auf: $result[username]";
            } else {
                $response['output'] = "Kein Eintrag für $target_ip gefunden.";
            }
        } else {
            $response['output'] = "Whois Fehler: Ungültiges IP-Format.";
        }
        break;
    
    // 'clear' wird im Frontend in desktop.js gehandhabt, aber wir verhindern eine Fehlermeldung.
    case 'clear':
         $response['output'] = ''; // Sende nichts zurück, das Frontend leert sich selbst
         break;
}

echo json_encode($response);