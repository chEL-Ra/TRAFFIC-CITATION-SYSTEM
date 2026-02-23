<?php
require 'db.php';
header('Content-Type: application/json');

$tct_no = $_GET['tct_no'] ?? '';

if ($tct_no) {
    // Assuming you have a separate table for multiple violations per ticket
    $stmt = $pdo->prepare("SELECT violation_name FROM ticket_violations WHERE tct_no = ?");
    $stmt->execute([$tct_no]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}