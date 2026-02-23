<?php
require 'db.php';
$parent_id = $_GET['parent_id'] ?? 0;
$stmt = $pdo->prepare("SELECT id, model_name AS name FROM vehicle_models WHERE type_id = ?");
$stmt->execute([$parent_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));