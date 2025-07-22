<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !isset($_POST['command'])) {
    echo json_encode(['output' => 'Fehler: Nicht autorisierter Zugriff.']);
    exit;
}
require 'db_connect.php';

// --- Globale Variablen & Befehls-Parsing ---
$user_id = $_SESSION['user_id'];
$full_command = trim($_POST['command']);
$parts = explode(' ', $full_command);
$program_name = strtolower($parts[0]);
$arguments = array_slice($parts, 1);

// --- Befehls-Router ---
$response = ['output' => "Befehl nicht gefunden: '" . htmlspecialchars($program_name) . "'."];

switch ($program_name) {
    case 'nmap':
        $target_ip = end($arguments); // Das Ziel ist immer das letzte Argument
        $flags = array_slice($arguments, 0, -1); // Alle Argumente davor sind Schalter/Flags

        if (!filter_var($target_ip, FILTER_VALIDATE_IP)) {
            $response['output'] = "Nmap Fehler: Ungültiges oder fehlendes Ziel.";
            break;
        }

        // Suche nach dem Ziel in der Datenbank (Spieler oder NPC)
        $stmt = $pdo->prepare("SELECT u.username, d.* FROM player_devices d JOIN users u ON d.user_id = u.id WHERE u.ip_address = :ip");
        $stmt->execute([':ip' => $target_ip]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            $response['output'] = "Host " . htmlspecialchars($target_ip) . " scheint down zu sein.";
            break;
        }

        // Logik für den "-A" (Aggressiver Scan) Schalter
        if (in_array('-A', $flags)) {
            $output = "Starte Nmap Aggressiv-Scan für " . htmlspecialchars($target_ip) . "...\n";
            $output .= "  Hostname: " . htmlspecialchars($device['hostname']) . "\n";
            $output .= "  Betriebssystem: " . htmlspecialchars($device['os_type']) . "\n";
            
            $ports_data = json_decode($device['ports'], true);
            if (!empty($ports_data)) {
                 $output .= "Offene Ports:\n";
                 foreach ($ports_data as $port => $details) {
                    if ($details['status'] === 'open') {
                        $output .= "    " . $port . "/tcp - " . htmlspecialchars($details['service'] ?? 'unknown') . "\n";
                    }
                }
            }
            $response['output'] = $output;
        } else {
            // Standard-Port-Scan (ohne Schalter)
            $output = "Starte Nmap Port Scan für " . htmlspecialchars($target_ip) . "...\n";
            $ports_data = json_decode($device['ports'], true);
            $output .= "PORT    STATE    SERVICE\n";
             foreach ($ports_data as $port => $details) {
                if ($details['status'] === 'open') {
                    $output .= str_pad($port . "/tcp", 8) . str_pad("open", 9) . htmlspecialchars($details['service'] ?? 'unknown') . "\n";
                }
            }
            $response['output'] = $output;
        }
        break;
    
    // Hier können weitere Befehle wie 'whois' etc. eingefügt werden

}

echo json_encode($response);