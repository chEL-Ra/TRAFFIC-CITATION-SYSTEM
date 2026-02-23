<?php
require 'db.php'; // Ensure this contains your PDO connection

$id = $_GET['id'] ?? 0;

if (!$id) {
    die("Error: No Payment ID provided.");
}

// Fetch Payment details joined with Citation details
// Fetch Payment details joined with updated Citation address columns
$sql = "SELECT p.*, 
               c.driver_fn, 
               c.driver_ln, 
               c.driver_mi,
               c.plate_no, 
               c.vehicle_type,
               CONCAT_WS(', ', c.driver_brgy, c.driver_muni, c.driver_prov) AS driver_address
        FROM payments p
        JOIN citations c ON p.tct_no = c.tct_no
        WHERE p.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Error: Receipt record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OR_<?= $payment['or_number'] ?></title>
    <style>
        /* Thermal Printer Styles (80mm) */
        body {
            font-family: "Courier New", Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .label { font-weight: bold; }
        
        .total-section {
            text-align: center;
            margin: 15px 0;
            padding: 5px;
            border: 1px solid #000;
        }
        .total-section h1 { margin: 0; font-size: 24px; }

        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 20px;
        }

        /* Hide buttons during actual printing */
        @media print {
            .no-print { display: none; }
            body { width: 100%; border: none; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print" style="text-align:right; margin-bottom: 20px;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close Window</button>
    </div>

    <div class="header">
        <h2>MUNICIPAL TRAFFIC OFFICE</h2>
        <p>LTO Department</p>
        <p>Official Payment Receipt</p>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span class="label">OR Number:</span>
        <span><?= htmlspecialchars($payment['or_number']) ?></span>
    </div>
    <div class="info-row">
        <span class="label">Date:</span>
        <span><?= date("M d, Y H:i", strtotime($payment['date_paid'])) ?></span>
    </div>
    <div class="info-row">
        <span class="label">TCT No:</span>
        <span><?= htmlspecialchars($payment['tct_no']) ?></span>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span class="label">Driver:</span>
        <span><?= htmlspecialchars($payment['driver_fn'] . " " . $payment['driver_ln']) ?></span>
    </div>
    <div class="info-row">
        <span class="label">Plate No:</span>
        <span><?= htmlspecialchars($payment['plate_no']) ?></span>
    </div>
    <div class="info-row">
        <span class="label">Vehicle:</span>
        <span><?= htmlspecialchars($payment['vehicle_type']) ?></span>
    </div>

    <div class="divider"></div>

    <div class="total-section">
        <p>AMOUNT PAID</p>
        <h1>₱ <?= number_format($payment['amount_paid'], 2) ?></h1>
    </div>

    <div class="info-row">
        <span class="label">Status:</span>
        <span>SETTLED / PAID</span>
    </div>
    <div class="info-row">
        <span class="label">Cashier:</span>
        <span><?= htmlspecialchars($payment['recorded_by']) ?></span>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>This serves as an official proof of payment.</p>
        <p><strong>Drive Safely!</strong></p>
        <p><?= date("Y-m-d H:i:s") ?></p>
    </div>

</body>
</html>