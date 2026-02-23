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

        // 2. Handle Impoundable Checkbox
        // Logic: If the 'impoundable' array exists and isn't empty, mark as 1
        $is_impoundable = (!empty($_POST['impoundable'])) ? 1 : 0;

    // ===============================
    // 1. INSERT MAIN CITATION
    // ===============================
    $sql = "INSERT INTO citations (
        tct_no, driver_ln, driver_fn, driver_mi,
        license_type, license_no,
        driver_brgy, driver_muni, driver_prov,
        plate_no, vehicle_type, vehicle_model,
        owner_ln, owner_fn, owner_mi, owner_contact,
        owner_brgy, owner_muni, owner_prov,
        date_apprehension, time_apprehension,
        apprehension_barangay, apprehension_municipality,
        apprehension_province, officer_id
    ) VALUES (
        ?,?,?,?,?,?,
        ?,?,?,?,?,
        ?,?,?,?,?,
        ?,?,?,?,?,
        ?,?,?,?
    )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        strtoupper($_POST['tct_no'] ?? ''),
        strtoupper($_POST['driver_ln'] ?? ''),
        strtoupper($_POST['driver_fn'] ?? ''),
        strtoupper($_POST['driver_mi'] ?? ''),
        strtoupper($_POST['license_type'] ?? ''),
        strtoupper($_POST['license_no'] ?? ''),
        strtoupper($_POST['driver_brgy'] ?? ''),
        strtoupper($_POST['driver_muni'] ?? ''),
        strtoupper($_POST['driver_prov'] ?? ''),
        strtoupper($_POST['plate_no'] ?? ''),
        $_POST['vehicle_type'] ?? null,
        $_POST['vehicle_model'] ?? null,
        strtoupper($_POST['owner_ln'] ?? ''),
        strtoupper($_POST['owner_fn'] ?? ''),
        strtoupper($_POST['owner_mi'] ?? ''),
        $_POST['owner_contact'] ?? '',
        strtoupper($_POST['owner_brgy'] ?? ''),
        strtoupper($_POST['owner_muni'] ?? ''),
        strtoupper($_POST['owner_prov'] ?? ''),
        $_POST['date_apprehension'] ?? null,
        $_POST['time_apprehension'] ?? null,
        strtoupper($_POST['app_barangay'] ?? ''),
        strtoupper($_POST['app_municipality'] ?? ''),
        strtoupper($_POST['app_province'] ?? ''),
        $_POST['officer_id'] ?? null
    ]);

    $id = $pdo->lastInsertId();

    // ===============================
    // 2. INSERT VIOLATIONS
    // ===============================
$is_impoundable = 0;
if (!empty($_POST['impoundable'])) {
    $is_impoundable = 1; // Set to 1 if any checkbox was ticked
}

$sql = "INSERT INTO citations (
            tct_no, ..., apprehension_province, impoundable
        ) VALUES (
            ?, ..., ?, ?
        )";

// ... add the $is_impoundable value to your $stmt->execute array ...

// --- 2. Insert into ticket_violations
if (!empty($_POST['violations'])) {
    // Use the actual TCT number provided in the form to link them
    $tct_no = $_POST['tct_no']; 

    $violationSQL = "INSERT INTO ticket_violations (tct_no, violation_name) VALUES (?, ?)";
    $violationStmt = $pdo->prepare($violationSQL);

    foreach ($_POST['violations'] as $index => $violation) {
        if (empty($violation)) continue;

        $violationStmt->execute([
            $tct_no,
            strtoupper($violation)
        ]);
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

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
}