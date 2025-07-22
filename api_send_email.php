<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert.']);
    exit;
}

require 'db_connect.php';

$sender_id = $_SESSION['user_id'];
$recipient_email = $_POST['to'] ?? '';
$subject = $_POST['subject'] ?? 'Kein Betreff';
$body = $_POST['body'] ?? '';

if (empty($recipient_email)) {
    echo json_encode(['success' => false, 'message' => 'Empfänger erforderlich.']);
    exit;
}

// Finde die ID des Empfängers anhand seiner E-Mail-Adresse
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $recipient_email]);
$recipient_id = $stmt->fetchColumn();

if (!$recipient_id) {
    echo json_encode(['success' => false, 'message' => 'Fehler: Empfänger nicht gefunden.']);
    exit;
}

// Hole den Namen des Absenders
$stmt_sender = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt_sender->execute([':id' => $sender_id]);
$sender_name = $stmt_sender->fetchColumn();


// Füge die E-Mail in die Datenbank ein
try {
    $sql = "INSERT INTO emails (recipient_id, sender_name, sender_email, subject, body_html, sent_at, is_read) 
            VALUES (:recipient_id, :sender_name, 'system@bnd.de', :subject, :body, NOW(), 0)";
    $stmt_insert = $pdo->prepare($sql);
    $stmt_insert->execute([
        ':recipient_id' => $recipient_id,
        ':sender_name' => $sender_name,
        ':subject' => $subject,
        ':body' => $body
    ]);
    echo json_encode(['success' => true, 'message' => 'E-Mail erfolgreich gesendet!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler.']);
}