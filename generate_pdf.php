<?php 
require_once 'db.php';
require('fpdf/fpdf.php'); 

// 1. Capture Inputs
$start  = $_GET['start_date'] ?? '';
$end    = $_GET['end_date']   ?? '';
$status = $_GET['status']     ?? 'all';

if (!$start || !$end) {
    die("Error: Please select both a start and end date.");
}

// 2. Build the Dynamic Query
// We use LEFT JOIN so we don't lose citations that haven't been paid yet
$sql = "SELECT c.*, p.status as p_status, p.or_number, p.amount_paid 
        FROM citations c 
        LEFT JOIN payments p ON c.tct_no = p.tct_no 
        WHERE c.date_apprehension BETWEEN ? AND ?";

if ($status === 'settled') {
    $sql .= " AND p.status = 'Paid'";
} elseif ($status === 'unsettled') {
    // Unsettled means either the payment record doesn't exist or status isn't 'Paid'
    $sql .= " AND (p.status IS NULL OR p.status != 'Paid')";
}

$sql .= " ORDER BY c.date_apprehension ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Create PDF
$pdf = new FPDF('L', 'mm', 'A4'); // Landscape for more columns
$pdf->AddPage();

// --- HEADER SECTION ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 7, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, 'LILOAN TRAFFIC COMMISSION (LITCOM)', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 5, 'Liloan, Cebu, Philippines', 0, 1, 'C');
$pdf->Ln(5);
$pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY()); // Horizontal divider line
$pdf->Ln(5);

// --- STATISTICS MINI-TABLE --- 
// Calculate counts before rendering
$countSettled = 0;
$totalAmt = 0;
foreach($rows as $r) { 
    if($r['p_status'] == 'Paid') { 
        $countSettled++; 
        $totalAmt += $r['amount_paid']; 
    } 
}

// --- REPORT INFO BOX ---
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 10, "OFFICIAL CITATION SUMMARY REPORT", 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(140, 7, "Date Range: " . date('M d, Y', strtotime($start)) . " to " . date('M d, Y', strtotime($end)), 0, 0);
$pdf->Cell(0, 7, "Generated On: " . date('M d, Y h:i A'), 0, 1, 'R');
$pdf->Ln(5);

// --- TABLE HEADER ---
$pdf->SetFillColor(29, 53, 87); 
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 10, 'TCT NO', 1, 0, 'C', true);
$pdf->Cell(60, 10, 'DRIVER NAME', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'PLATE', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'DATE', 1, 0, 'C', true);
$pdf->Cell(55, 10, 'OFFENSE TYPE', 1, 0, 'C', true); 
$pdf->Cell(25, 10, 'STATUS', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'OR NO', 1, 0, 'C', true);
$pdf->Cell(27, 10, 'AMOUNT', 1, 1, 'C', true);

// --- DATA ROWS ---
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);
$fill = false; 
foreach ($rows as $row) {
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(25, 8, $row['tct_no'], 1, 0, 'C', $fill);
    $pdf->Cell(60, 8, strtoupper(($row['driver_fn'] ?? '') . ' ' . ($row['driver_ln'] ?? '')), 1, 0, 'L', $fill);
    $pdf->Cell(30, 8, $row['plate_no'], 1, 0, 'C', $fill);
    $pdf->Cell(30, 8, date('m/d/Y', strtotime($row['date_apprehension'])), 1, 0, 'C', $fill);
    $pdf->Cell(55, 8, substr($row['specific_violation'] ?? 'N/A', 0, 30), 1, 0, 'L', $fill);
    
    if (($row['p_status'] ?? '') == 'Paid') {
        $pdf->SetTextColor(0, 100, 0);
        $status_text = 'SETTLED';
    } else {
        $pdf->SetTextColor(150, 0, 0);
        $status_text = 'UNSETTLED';
    }
    
    $pdf->Cell(25, 8, $status_text, 1, 0, 'C', $fill);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(25, 8, $row['or_number'] ?? '---', 1, 0, 'C', $fill);
    $pdf->Cell(27, 8, number_format($row['amount_paid'] ?? 0, 2), 1, 1, 'R', $fill);
    $fill = !$fill; 
}

// --- SIGNATORY SECTION ---
$pdf->Ln(20);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 5, 'Prepared by:', 0, 0);
$pdf->Cell(95, 5, 'Reviewed by:', 0, 0);
$pdf->Cell(95, 5, 'Approved by:', 0, 1);
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 5, '__________________________', 0, 0);
$pdf->Cell(95, 5, '__________________________', 0, 0);
$pdf->Cell(95, 5, 'MR. NEIL CANETE', 0, 1);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(95, 5, 'TCMS Staff', 0, 0);
$pdf->Cell(95, 5, 'Finance Officer', 0, 0);
$pdf->Cell(95, 5, 'LITCOM Head', 0, 1);

// Clean buffer and output
ob_end_clean();
$pdf->Output('I', "Traffic_Report_{$start}_{$end}.pdf");