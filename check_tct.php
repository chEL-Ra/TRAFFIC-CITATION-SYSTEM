<?php
require_once 'db.php';
 $tct_no = $_GET['tct_no'] ?? '';

 if ($tct_no) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM citations WHERE tct_no = ?");
    $stmt->execute([$tct_no]);
    $exists = $stmt->fetchColumn() > 0;

    echo json_encode(['exists' => $exists]);
 }
 ?>