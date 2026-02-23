<?php
// 1. Point to the manual Dompdf autoloader
require_once 'dompdf/autoload.inc.php'; 
require_once 'db.php'; // Your DB connection

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. Setup Options
$options = new Options();
$options->set('isRemoteEnabled', true); // Useful if you want to use images/CSS
$dompdf = new Dompdf($options);

// 3. Get Payment ID
$payment_id = $_GET['payment_id'] ?? 0;

// 4. Fetch from DB (Ensuring or_number exists now!)
$stmt = $pdo->prepare("
    SELECT p.*, o.offender_name, o.offense_type 
    FROM payments p 
    JOIN offenses o ON p.offense_id = o.id 
    WHERE p.id = ?
");
$stmt->execute([$payment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Payment record not found.");
}

// 5. Build the HTML
$html = "
    <h1>Traffic Citation Receipt</h1>
    <p><strong>OR Number:</strong> {$data['or_number']}</p>
    <p><strong>Driver:</strong> {$data['offender_name']}</p>
    <p><strong>Violation:</strong> {$data['offense_type']}</p>
    <p><strong>Amount:</strong> ₱" . number_format($data['amount_paid'], 2) . "</p>
";

// 6. Generate the PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();
$dompdf->stream("Receipt_" . $data['or_number'] . ".pdf", ["Attachment" => false]);