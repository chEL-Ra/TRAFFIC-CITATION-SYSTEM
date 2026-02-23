<?php
require 'db.php';

if (!isset($_GET['type_id']) || empty($_GET['type_id'])) {
    echo '<option value="">Select Brand</option>';
    exit;
}

$type_id = intval($_GET['type_id']);

$stmt = $pdo->prepare("
    SELECT id, brand_name 
    FROM vehicle_brands 
    WHERE type_id = ? 
    ORDER BY brand_name ASC
");
$stmt->execute([$type_id]);
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($brands) {
    echo '<option value="">Select Brand</option>';
    foreach ($brands as $brand) {
        echo '<option value="'.$brand['id'].'">'
             .htmlspecialchars($brand['brand_name']).
             '</option>';
    }
} else {
    echo '<option value="">No Brands Found</option>';
}
