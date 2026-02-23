<?php
require 'db.php';

if (!isset($_GET['brand_id']) || empty($_GET['brand_id'])) {
    echo '<option value="">Select Model</option>';
    exit;
}

$brand_id = intval($_GET['brand_id']);

$stmt = $pdo->prepare("
    SELECT id, model_name 
    FROM vehicle_models 
    WHERE brand_id = ? 
    ORDER BY model_name ASC
");
$stmt->execute([$brand_id]);
$models = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($models) {
    echo '<option value="">Select Model</option>';
    foreach ($models as $model) {
        echo '<option value="'.$model['id'].'">'
             .htmlspecialchars($model['model_name']).
             '</option>';
    }
} else {
    echo '<option value="">No Models Found</option>';
}
