<?php
require_once 'db.php';
session_start();

// 1. FIX THE TIMEZONE (Sets it to Philippine Time)
date_default_timezone_set('Asia/Manila');

// 2. EMBED THE LOGO (Base64)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Ensure this path matches your folder exactly (e.g., /Traffic-Citation-SYSTEM/assets/logo.png)
$logo_url = $protocol . $host . "/Traffic-Citation-SYSTEM/logos/litcom_logo.png";


// 3. Capture Filters
$from_date    = $_GET['from_date']    ?? '';
$to_date      = $_GET['to_date']      ?? '';
$report_type  = $_GET['report_type']  ?? 'all';
$offense_type = $_GET['offense_type'] ?? 'All Offenses';
$search       = $_GET['search']       ?? '';

// 4. Database Query
$sql = "SELECT c.*, 
        p.status AS p_status, p.or_number, 
        o.name AS officer_name,
        -- Address Logic
        CONCAT_WS(', ', 
            NULLIF(TRIM(c.apprehension_barangay), ''), 
            NULLIF(c.apprehension_municipality, ''), 
            NULLIF(c.apprehension_province, '')
        ) AS place_apprehension,
        
        COALESCE(vt.type_name, NULLIF(c.vehicle_type_other, ''), 'N/A') AS display_type, 
        COALESCE(vb.brand_name, NULLIF(c.vehicle_brand_other, ''), 'N/A') AS display_brand, 
        COALESCE(vm.model_name, NULLIF(c.vehicle_model_other, ''), 'N/A') AS display_model

        FROM citations c
        LEFT JOIN payments p ON c.tct_no = p.tct_no
        LEFT JOIN officers o ON c.officer_id = o.id
        LEFT JOIN vehicle_types vt ON c.vehicle_type = vt.id
        LEFT JOIN vehicle_models vm ON c.vehicle_model = vm.id
        LEFT JOIN vehicle_brands vb ON vm.brand_id = vb.id -- Brand linked via Model ID
        WHERE 1=1";
$params = [];

if ($report_type === 'settled') $sql .= " AND p.status = 'Paid'";
elseif ($report_type === 'unsettled') $sql .= " AND (p.status IS NULL OR p.status != 'Paid')";

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND c.date_apprehension BETWEEN ? AND ?";
    $params[] = $from_date; $params[] = $to_date;
}

if ($offense_type !== 'All Offenses') {
    $sql .= " AND c.specific_violation = ?";
    $params[] = $offense_type;
}

if (!empty($search)) {
    $sql .= " AND (c.driver_fn LIKE ? OR c.driver_ln LIKE ? OR c.plate_no LIKE ?)";
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_count = count($rows);

// 5. Excel Headers
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Traffic_Report_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo '<table border="1">';

// --- PRESENTABLE HEADER ---
$base64Logo = ''; 
$logoPath = 'logos/litcom_logo.png'; 

if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

echo '<tr>
        <th colspan="13" style="background-color: #ffffff; border: none; height: 130px; text-align:center; vertical-align:middle;">';
        
        // Add this specific block to display the image
        if (!empty($base64Logo)) {
            echo '<img src="' . $base64Logo . '" width="90" height="90" style="display:block; margin:auto;" />';
        }

echo '<br>
            <font size="3">Republic of the Philippines</font><br>
            <font size="5"><b>TRAFFIC MANAGEMENT OFFICE</b></font><br>
            <font size="2">Liloan, Central Visayas, 6002</font>
        </th>
      </tr>';

echo '<tr>
        <th colspan="15" style="background-color: #f2f2f2; border: 1px solid #000; height: 30px; text-align:center;">
            <b>SUMMARY OF TRAFFIC CITATIONS</b>
        </th>
      </tr>';

// Metadata Row (Uses the corrected timezone)
echo '<tr>
<th colspan="8" style="text-align:left;">
           <b>Report Period:</b> ' .
            ($from_date ? "$from_date to $to_date" : "All Time Records") .
        '</th>
     <th colspan="7" style="text-align:right;">
            <b>Generated on:</b> ' . date('F d, Y | h:i A') . '
        </th>
      </tr>';

// --- TABLE HEADERS ---
echo '<tr style="background-color: #004d00; color: #ffffff; text-align:center; font-weight: bold;">
        <th>Date</th>
        <th>Time</th>
        <th>TCT No.</th>
        <th>Place of Apprehension</th>
        <th>Driver Name</th>
        <th>Plate No.</th>
        <th>Type</th>
        <th>Model</th>
        <th>Brand</th>
        <th>Registered Owner</th>
        <th>Violation(s)</th>
        <th>Officer Name</th>
        <th>Officer ID</th>
        <th>Status</th>
        <th>OR Number</th>
      </tr>';

// --- DATA ROWS ---
// --- DATA ROWS ---
foreach($rows as $row) 
    // 1. Initialize variables with fallback values to prevent "Undefined" warnings
    $status = ($row['p_status'] == 'Paid') ? 'Settled' : 'Unsettled';
    $or_number = htmlspecialchars($row['or_number'] ?? '---');
    
    // Use the alias names from the SQL COALESCE logic
    foreach($rows as $row) {
    $vehicle_type  = htmlspecialchars($row['display_type']);
    $vehicle_brand = htmlspecialchars($row['display_brand']);
    $vehicle_model = htmlspecialchars($row['display_model']);

    $place_apprehension = !empty($row['place_apprehension']) ? htmlspecialchars($row['place_apprehension']) : 'N/A';
    $driver = htmlspecialchars(($row['driver_fn'] ?? '') . ' ' . ($row['driver_ln'] ?? ''));
    $owner = trim(($row['owner_fn'] ?? '') . ' ' . ($row['owner_ln'] ?? '')) ?: 'N/A';

    // 2. Violation Fetching
    $stmt_v = $pdo->prepare("SELECT violation_name FROM ticket_violations WHERE tct_no = ?");
    $stmt_v->execute([$row['tct_no']]);
    $violations = $stmt_v->fetchAll(PDO::FETCH_COLUMN);
    $violation_text = $violations ? htmlspecialchars(implode("; ", $violations)) : 'N/A';

    // 3. Print Table Row
    echo "<tr>
            <td align='center'>".date('M d, Y', strtotime($row['date_apprehension']))."</td>
            <td align='center'>".date('h:i A', strtotime($row['time_apprehension']))."</td>
            <td align='center' style='mso-number-format:\"\\@\";'>".htmlspecialchars($row['tct_no'])."</td>
            <td>{$place_apprehension}</td>
            <td>{$driver}</td>
            <td align='center' style='mso-number-format:\"\\@\";'>".htmlspecialchars($row['plate_no'])."</td>
            <td>{$vehicle_type}</td>
            <td>{$vehicle_model}</td>
            <td>{$vehicle_brand}</td>
            <td>".htmlspecialchars($owner)."</td>
            <td>{$violation_text}</td>
            <td>".htmlspecialchars($row['officer_name'] ?? 'N/A')."</td>
            <td align='center'>".htmlspecialchars($row['officer_id'] ?? 'N/A')."</td>
            <td align='center' style='font-weight:bold; color:".($status=='Settled'?'#006400':'#8B0000').";'>{$status}</td>
            <td align='center' style='mso-number-format:\"\\@\";'>{$or_number}</td>
          </tr>";
}
// --- TOTAL COUNT FOOTER ---
echo '<tr style="background-color: #f2f2f2; font-weight: bold;">
        <td colspan="14" align="right">GRAND TOTAL RECORDS:</td>
        <td align="center">'.$total_count.'</td>
      </tr>';

// --- GENERATED BY FOOTER ---
$admin_name = $_SESSION['admin_name'] ?? 'Authorized Personnel';
echo '<tr><td colspan="13" style="border:none; height:40px;"></td></tr>';
echo '<tr>
        <td colspan="8" style="border:none;"></td>
        <td colspan="5" style="border:none; text-align:center;">
            <div style="border-top: 1px solid #000; width: 250px; margin: 0 auto;"></div>
            <b>' . strtoupper($admin_name) . '</b><br>
            System Administrator<br>
            <small>Electronic Signature - Verified Document</small>
        </td>
      </tr>';

echo '</table>';
exit;
?>