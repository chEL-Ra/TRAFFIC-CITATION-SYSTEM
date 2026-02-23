<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Database Connection (Ensure $pdo is defined in db.php)
require 'db.php'; 

$message = ""; 
$error = "";
$print_script = ""; // Initialize the script variable
/* ===============================
    HANDLE ADD PAYMENT
================================ */

$limit = 10;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// Count total payments for pagination math
$countStmt = $pdo->query("SELECT COUNT(*) FROM payments");
$total_records = $countStmt->fetchColumn();
$total_pages = ($total_records > 0) ? ceil($total_records / $limit) : 1;

if(isset($_POST['add_payment'])){
    $tct_no = trim($_POST['tct_no']); 
    $or_number = trim($_POST['or_number']);
    $amount_paid = floatval($_POST['amount_paid']);
    $date_paid = $_POST['date_paid'];
    $recorded_by = $_SESSION['SESS_MEMBER_NAME'] ?? 'Unknown Officer';

    // 1. Verify the TCT Number exists
    $stmt = $pdo->prepare("SELECT id FROM citations WHERE tct_no = ?");
    $stmt->execute([$tct_no]);
    $citation = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$citation){
        $error = "Ticket Number (TCT No: $tct_no) not found in records.";
    } else {
        // 2. Check if OR Number already exists
        $stmtCheckOR = $pdo->prepare("SELECT id FROM payments WHERE or_number = ?");
        $stmtCheckOR->execute([$or_number]);
        if($stmtCheckOR->fetch()){
            $error = "OR Number already exists in the system.";
        }

        // 3. Check if already paid
        $stmtPaid = $pdo->prepare("SELECT id FROM payments WHERE tct_no = ?");
        $stmtPaid->execute([$tct_no]);
        if($stmtPaid->fetch()){
            $error = "This ticket (TCT No: $tct_no) has already been paid.";
        }

        if(!$error){
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO payments 
                    (tct_no, or_number, amount_paid, status, date_paid, recorded_by)
                    VALUES (?, ?, ?, 'Paid', ?, ?)
                ");

                $stmtInsert->execute([
                    $tct_no,
                    $or_number,
                    $amount_paid,
                    $date_paid,
                    $recorded_by
                ]);

                $new_id = $pdo->lastInsertId(); // Capture ID for the receipt
                $message = "Payment for TCT #$tct_no recorded successfully!";

                // Generate the Print Trigger Script
                $print_script = "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: '$message',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '🖨️ Print Receipt',
                        cancelButtonText: 'Done',
                        confirmButtonColor: '#198754'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('print_receipt.php?id=$new_id', '_blank', 'width=450,height=600');
                        }
                    });
                });
                </script>";

            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

/* ===============================
    FETCH PAYMENTS FOR DISPLAY
================================ */
$sql = "SELECT p.*, c.driver_fn, c.driver_ln, c.plate_no 
        FROM payments p
        JOIN citations c ON p.tct_no = c.tct_no
        ORDER BY p.date_paid DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?= $print_script; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <?php if($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?= $print_script ?>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card p-4 mb-4 shadow-sm border-0">
        <h4 class="mb-3 text-success">💾 Record New Payment</h4>
        <form method="POST">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">TCT Number</label>
                    <input type="text" id="tct_no" name="tct_no" class="form-control" placeholder="TCT-0000" required>
                    <div id="tctFeedback" class="form-text"></div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">OR Number</label>
                    <input type="text" name="or_number" class="form-control" placeholder="Official Receipt #" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Amount Paid</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" name="amount_paid" step="0.01" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Date Paid</label>
                    <input type="date" name="date_paid" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" name="add_payment" class="btn btn-success px-4">Process Payment</button>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">Payment History</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>TCT No.</th>
                        <th>Driver Name</th>
                        <th>OR Number</th>
                        <th>Amount</th>
                        <th>Date Paid</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="7" class="text-center py-4">No payment records found.</td></tr>
                    <?php else: ?>
                        <?php foreach($payments as $p): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($p['tct_no']) ?></td>
                                <td><?= htmlspecialchars($p['driver_fn'] . " " . $p['driver_ln']) ?></td>
                                <td><code><?= htmlspecialchars($p['or_number']) ?></code></td>
                                <td class="fw-bold text-success">₱<?= number_format($p['amount_paid'], 2) ?></td>
                                <td><?= date("M d, Y", strtotime($p['date_paid'])) ?></td>
                                <td><span class="badge bg-success">Settled</span></td>
                                <td class="text-center">
                                    <button onclick="window.open('print_receipt.php?id=<?= $p['id'] ?>', '_blank', 'width=450,height=600')" 
                                            class="btn btn-sm btn-outline-secondary" title="Reprint Receipt">
                                        🖨️ Print
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
     <div class="d-flex justify-content-between align-items-center mt-3">
<small class="text-muted">Showing page <?= $page ?> of <?= $total_pages ?></small>
    
    <nav>
    <ul class="pagination pagination-sm m-0">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>">Previous</a>
        </li>

        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <?php if($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php elseif($i == $page - 3 || $i == $page + 3): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
        <?php endfor; ?>

        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>">Next</a>
        </li>
    </ul>
</nav>
</div>
</div>
        </div>
    </div>
</div>

<script>
// Live TCT validation via AJAX
document.getElementById('tct_no').addEventListener('blur', function(){
    let tct = this.value;
    let feedback = document.getElementById('tctFeedback');
    if(tct !== ""){
        fetch('check_tct.php?tct=' + tct)
        .then(res => res.json())
        .then(data => {
            if(data.success){
                feedback.innerHTML = "✅ Found: " + data.driver_name;
                feedback.className = "form-text text-success";
            } else {
                feedback.innerHTML = "❌ Ticket Number not found";
                feedback.className = "form-text text-danger";
            }
        })
        .catch(err => console.error('Error fetching TCT:', err));
    }
});
</script>
</body>
</html>