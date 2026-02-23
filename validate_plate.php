<?php
require 'db.php';
$stmt = $pdo->prepare("SELECT id FROM vehicles WHERE plate_number=?");
$stmt->execute([$_GET['plate']]);
echo json_encode(["exists"=>$stmt->fetch() ? true : false]);
?>