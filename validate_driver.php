<?php
require 'db.php';
$stmt = $pdo->prepare("SELECT id FROM drivers WHERE license_number=?");
$stmt->execute([$_GET['license']]);
echo json_encode(["exists"=>$stmt->fetch() ? true : false]);
?>