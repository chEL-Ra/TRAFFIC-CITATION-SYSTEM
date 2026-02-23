<?php
session_start();
if(!isset($_SESSION['SESS_MEMBER_ID'])) header("Location: login.php");
require 'db.php';

// Stats
$total_offenses = $pdo->query("SELECT COUNT(*) FROM citations")->fetchColumn();

// Total paid fines
$total_paid = $pdo->query("
    SELECT COUNT(DISTINCT p.tct_no) 
    FROM payments p
    INNER JOIN citations c ON p.tct_no = c.tct_no 
    WHERE p.status = 'Paid'
")->fetchColumn();

// Total unpaid fines - Ensure this matches your view logic
$total_unpaid = $total_offenses - $total_paid; //

// Determine current page
$page = $_GET['page'] ?? 'home';

// Old Query: SELECT * FROM citations
// New Secret Query:
$query = "SELECT * FROM citations WHERE is_deleted = 0";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Traffic Citation Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap & Chart.js -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background:#eef1f5; font-family:'Segoe UI',sans-serif; margin:0; }

#wrapper { display:flex; min-height:100vh; }

#sidebar-wrapper { width:220px; background:#1d3557; }
#page-content-wrapper { flex:1; padding:30px; }

#sidebar-wrapper .list-group-item { color:white; background:#1d3557; border:none; }
#sidebar-wrapper .list-group-item:hover { background:#457b9d; }

.gov-header {
    background:#f8f9fa; border-radius:8px; padding:15px 25px;
    display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;
}
.gov-header img { height:80px; margin-right:10px; }
.stat-card { border-radius:14px; padding:25px 20px; color:white; text-align:center; height:160px; box-shadow:0 6px 18px rgba(0,0,0,0.08); }
.card-total { background:#1d3557; }
.card-unpaid { background:#d62828; }
.card-paid { background:#2a9d8f; }
.stat-number { font-size:2.4rem; font-weight:700; }
.chart-box { background:white; padding:30px; border-radius:14px; margin-top:40px; height:420px; }
.report-section { background:white; padding:25px; border-radius:12px; margin-top:40px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
</style>
</head>
<body>

<div id="wrapper">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <!-- Government Header -->
        <div class="gov-header">
            <!-- Left: Logos -->
            <div class="d-flex align-items-center">
                <img src="logos/municipality_seal.png" alt="Municipality Seal">
                <img src="logos/litcom_logo.png" alt="Liloan Traffic Commission">
            </div>

            <!-- Center: Title -->
            <div class="text-center flex-grow-1 px-3">
                <h2>LILOAN TRAFFIC COMMISSION</h2>
                <p>During the administration of <strong>MAYOR ALJEW FRASCO</strong></p>
                <p>MR. NEIL CAÑETE - LITCOM Head</p>
                <p><small>In-house Developed by: Edwin G. Yuson - ICT</small></p>
            </div>

            <!-- Right: Live Clock -->
            <div>
                <span id="liveTime" style="font-weight:bold;"></span>
            </div>
        </div>

        <!-- Page Switcher -->
        <?php if($page=='home'): ?>

            <!-- Dashboard Cards -->
            <div class="row g-4">

    <!-- Total Offenses -->
    <div class="col-md-4">
        <div class="stat-card card-total">
            <div class="fw-semibold">Total Offenses</div>
            <div class="stat-number" data-target="<?= $total_offenses ?>">
                <?= $total_offenses ?>
            </div>
        </div>
    </div>

    <!-- Unpaid Fines -->
    <div class="col-md-4">
        <div class="stat-card card-unpaid">
            <div class="fw-semibold">Unpaid Fines</div>
            <div class="stat-number" data-target="<?= $total_unpaid ?>">
                <?= $total_unpaid ?>
            </div>
        </div>
    </div>

    <!-- Paid Fines -->
    <div class="col-md-4">
        <div class="stat-card card-paid">
            <div class="fw-semibold">Paid Fines</div>
            <div class="stat-number" data-target="<?= $total_paid ?>">
                <?= $total_paid ?>
            </div>
        </div>
    </div>
</div>
            <!-- Report Generator -->
      <div class="card p-4 shadow-sm mb-4 mt-4">
    <h5 class="mb-3">Generate Summary Report</h5>
    <form action="generate_pdf.php" method="GET" target="_blank">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Status</label>
                <select name="status" class="form-select">
                    <option value="all">All Records</option>
                    <option value="settled">Settled (Paid)</option>
                    <option value="unsettled">Unsettled (Unpaid)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    🖨️ Generate Report
                </button>
            </div>
        </div>
    </form>
</div>

        <?php elseif($page == 'add_offense'): ?>
            <?php include 'add_offense.php'; ?>
        <?php elseif($page == 'view_offenses'): ?>
            <?php include 'view_offenses.php'; ?>
        <?php elseif($page == 'payments'): ?>
            <?php include 'payments.php'; ?>
        <?php else: ?>
            <div class="alert alert-danger">Page not found.</div>
        <?php endif; ?>
</div>

<!-- Scripts -->
<script>
// Live Clock
function updateClock(){
    const now = new Date();
    document.getElementById('liveTime').innerHTML = now.toLocaleString();
}
setInterval(updateClock, 1000);
updateClock();

// Animated counters
document.querySelectorAll('.stat-number').forEach(counter=>{
    const target = +counter.getAttribute('data-target');
    let count=0;
    const increment = target/50;
    function update(){
        if(count < target){ count+=increment; counter.innerText=Math.ceil(count); setTimeout(update,20); }
        else counter.innerText=target;
    }
    update();
});

// Offense Chart
</script>

</body>
</html>
