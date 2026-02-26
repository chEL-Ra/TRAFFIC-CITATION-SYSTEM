<?php
ob_start();
header('Content-Type: application/json');
require_once 'db.php'; // use same connection file everywhere
date_default_timezone_set('Asia/Manila');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        // 1. Future Date Validation
        $input_date = $_POST['date_apprehension'];
        $input_time = $_POST['time_apprehension'];
        $combined_dt = strtotime("$input_date $input_time");
        
        if ($combined_dt > time()) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot save a citation for a future date/time.']);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Capture the dropdown IDs
// 1. Capture the Dropdown IDs (should be '0' if user is typing manually)
$v_type_id  = $_POST['vehicle_type'] ?? null;
$v_model_id = $_POST['vehicle_model'] ?? null;

// 2. Capture the Manual Text from the inputs in add.php
// We use strtoupper to keep your data uniform
$v_type_other  = strtoupper(trim($_POST['vehicle_type_other'] ?? ''));
$v_brand_other = strtoupper(trim($_POST['vehicle_brand_other'] ?? ''));
$v_model_other = strtoupper(trim($_POST['vehicle_model_other'] ?? ''));
// 3. Prepare and Execute
$sql = "INSERT INTO citations (
    tct_no, driver_ln, driver_fn, driver_mi,
    license_type, license_no, driver_brgy, driver_muni, driver_prov,
    plate_no, vehicle_type, vehicle_model,
    vehicle_type_other, vehicle_brand_other, vehicle_model_other,
    owner_ln, owner_fn, owner_mi, owner_contact,
    owner_brgy, owner_muni, owner_prov,
    date_apprehension, time_apprehension,
    apprehension_barangay, apprehension_municipality, apprehension_province, 
    officer_id, impoundable, latitude, longitude
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    strtoupper($_POST['tct_no']), strtoupper($_POST['driver_ln']), strtoupper($_POST['driver_fn']), 
    strtoupper($_POST['driver_mi']), strtoupper($_POST['license_type']), strtoupper($_POST['license_no']), 
    strtoupper($_POST['driver_brgy']), strtoupper($_POST['driver_muni']), strtoupper($_POST['driver_prov']),
    strtoupper($_POST['plate_no']), 
    $v_type_id, $v_model_id, 
    $v_type_other, $v_brand_other, $v_model_other, // MANUAL TEXT SAVED HERE
    strtoupper($_POST['owner_ln']), strtoupper($_POST['owner_fn']), strtoupper($_POST['owner_mi']),
    $_POST['owner_contact'], strtoupper($_POST['owner_brgy']), strtoupper($_POST['owner_muni']),
    strtoupper($_POST['owner_prov']), $_POST['date_apprehension'], $_POST['time_apprehension'],
    strtoupper($_POST['app_barangay']), strtoupper($_POST['app_municipality']), strtoupper($_POST['app_province']),
    $_POST['officer_id'], ($_POST['impoundable'] ?? 'No'), $_POST['latitude'], $_POST['longitude']
]);
   if (!empty($_POST['violations'])) {
            $tct_no = strtoupper($_POST['tct_no']); 
            $violationSQL = "INSERT INTO ticket_violations (tct_no, violation_name) VALUES (?, ?)";
            $violationStmt = $pdo->prepare($violationSQL);

            foreach ($_POST['violations'] as $violation) {
                if (empty($violation)) continue;
                $violationStmt->execute([$tct_no, strtoupper($violation)]);
            }
        }

        $pdo->commit();

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Record saved successfully!'
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}