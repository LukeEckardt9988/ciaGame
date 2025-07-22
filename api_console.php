<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['command_id'])) {
    echo json_encode(['output' => 'Fehler: Nicht autorisierter Zugriff.']);
    exit;
}

require 'db_connect.php';

$command_id = $_POST['command_id'];
$argument = isset($_POST['argument']) ? trim($_POST['argument']) : '';
$response = ['output' => 'FATAL ERROR', 'unlocked_program' => null, 'failure_hint' => null]; // 'failure_hint' hinzugefügt

try {
    $sql = "SELECT * FROM commands WHERE id = :command_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':command_id', $command_id, PDO::PARAM_INT);
    $stmt->execute();
    $command_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($command_data) {
        $is_successful = ($command_data['correct_argument'] === null || $command_data['correct_argument'] == $argument);

        if ($is_successful) {
            $response['output'] = $command_data['success_output'];

            if (!empty($command_data['unlocks_program_id'])) {
                $unlocked_id = $command_data['unlocks_program_id'];
                $sql_unlock = "SELECT id, name, description FROM programs WHERE id = :id";
                $stmt_unlock = $pdo->prepare($sql_unlock);
                $stmt_unlock->bindParam(':id', $unlocked_id, PDO::PARAM_INT);
                $stmt_unlock->execute();
                if ($unlocked_data = $stmt_unlock->fetch(PDO::FETCH_ASSOC)) {
                    $response['unlocked_program'] = $unlocked_data;
                }
            }
        } else {  // FEHLERFALL
            $response['output'] = $command_data['failure_output'];
            // NEU: Sende auch den Hinweis, falls einer existiert
            if (!empty($command_data['failure_hint'])) {
                $response['failure_hint'] = $command_data['failure_hint'];
            }
        }
    }
} catch (PDOException $e) {
    $response['output'] = 'Datenbankfehler: ' . $e->getMessage();
}

echo json_encode($response);
