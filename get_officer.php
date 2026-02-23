<?php
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT name FROM officers WHERE id = ?");
    $stmt->execute([$id]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    echo $officer ? $officer['name'] : 'Officer not found';
}
?>