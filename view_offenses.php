<?php
require 'db.php';

// 1. DATA PREPARATION
$all_types = [
    "Reckless Driving", "DUI", "No License", "Expired Registration", 
    "Unauthorized/improvised number plates", "Overspeeding", "Illegal Parking", 
    "Running Red Light", "Using Mobile Phone While Driving", "Failure to Wear Seatbelt", 
    "Overloading", "Illegal U-Turn", "Expired Traffic Violation Receipt", 
    "Obstruction of Traffic", "Illegal Use of Hazard Lights", 
    "Failure to Yield to Pedestrians", "Illegal Use of Horn", 
    "Driving in the Wrong Lane", "Failure to Use Turn Signals", 
    "Illegal Use of Headlights", "Installation of jalousies, curtains, dim colored lights, etc", 
    "Illegal Window Tinting", "Illegal Use of Sirens", 
    "Operating a motor vehicle with a suspended or revoked Certificate of Registration", 
    "Operating a motor vehicle with a suspended or revoked driver's license", 
    "Illegal Racing", "Using license plates different from the body number"
];

$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$offense_type = $_GET['offense_type'] ?? 'All Offenses';
$report_type = $_GET['report_type'] ?? 'General';

// 2. PAGINATION & FILTER LOGIC
$limit = 10; 
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

$params = [];
$where = " WHERE 1=1";

if ($report_type === 'settled') {
    $where .= " AND p.status = 'Paid'";
} elseif ($report_type === 'unsettled') {
    $where .= " AND (p.status != 'Paid' OR p.status IS NULL)";
}

if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND c.date_apprehension >= ? AND c.date_apprehension <= ?";
    $params[] = $from_date;
    $params[] = $to_date;
}

if ($offense_type !== 'All Offenses') {
    $where .= " AND c.specific_violation = ?";
    $params[] = $offense_type;
}

if (!empty($search)) {
    $where .= " AND (CONCAT_WS(' ', c.driver_fn, c.driver_ln) LIKE ? OR c.plate_no LIKE ? OR c.tct_no LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm; 
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Get Total Count for Pagination
$count_sql = "SELECT COUNT(*) FROM citations c LEFT JOIN payments p ON c.tct_no = p.tct_no $where";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_rows = $stmt_count->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Main Query
$sql = "SELECT c.*, vb.brand_name, vm.model_name, vt.type_name, o.name AS officer_name, p.status AS p_status,
       (SELECT COUNT(*) FROM ticket_violations tv WHERE tv.tct_no = c.tct_no) AS violation_count
       FROM citations c
       LEFT JOIN vehicle_models vm ON c.vehicle_model = vm.id
       LEFT JOIN vehicle_brands vb ON vm.brand_id = vb.id 
       LEFT JOIN vehicle_types vt ON c.vehicle_type = vt.id
       LEFT JOIN officers o ON c.officer_id = o.id
       LEFT JOIN payments p ON c.tct_no = p.tct_no
       $where
       ORDER BY c.date_apprehension DESC
       LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Traffic Citation Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .main-header { background: #fff; padding: 20px; border-bottom: 3px solid #1e7e34; margin-bottom: 20px; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    <div class="main-header no-print d-flex justify-content-between align-items-center shadow-sm">
        <h2 class="text-danger fw-bold m-0">TRAFFIC CITATION REPORTS</h2>
    </div>

    <div class="card mb-4 no-print border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="dashboard.php" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="view_offenses">
                <input type="hidden" name="report_type" value="<?= htmlspecialchars($report_type) ?>">
                
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, Plate, or TCT..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">From</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $from_date ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">To</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $to_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Offense Type</label>
                    <select name="offense_type" class="form-select form-select-sm">
                        <option value="All Offenses">All Offenses</option>
                        <?php foreach($all_types as $type): ?>
                            <option value="<?= $type ?>" <?= ($offense_type == $type) ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">FILTER</button>
                    <a href="dashboard.php?page=view_offenses" class="btn btn-light btn-sm">RESET</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container shadow-sm p-3 bg-white rounded">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 text-secondary">Records (<?= $total_rows ?>)</h5>
            <a href="generate_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm">📊 Export to Excel</a>
        </div>
        
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>TCT No.</th>
                    <th>Driver</th>
                    <th>Plate</th>
                    <th># Violations</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($offenses)): ?>
                    <?php foreach ($offenses as $row): ?>
                        <?php $isPaid = ($row['p_status'] == 'Paid'); ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($row['date_apprehension'])) ?></td>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($row['tct_no']) ?></td>
                            <td><?= htmlspecialchars($row['driver_fn'] . " " . $row['driver_ln']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['plate_no']) ?></span></td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark"><?= $row['violation_count'] ?> Violation<?= $row['violation_count'] > 1 ? 's' : '' ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $isPaid ? 'bg-success' : 'bg-danger' ?>"><?= $isPaid ? 'Settled' : 'Unsettled' ?></span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary view-btn" 
                                        data-tct="<?= htmlspecialchars($row['tct_no']) ?>"
                                        data-plate="<?= htmlspecialchars($row['plate_no']) ?>"
                                        data-date="<?= date('M d, Y', strtotime($row['date_apprehension'])) ?>"
                                        data-time="<?= htmlspecialchars($row['time_apprehension']) ?>"
                                        data-officerid="<?= htmlspecialchars($row['officer_id']) ?>"
                                        data-officername="<?= htmlspecialchars($row['officer_name'] ?? 'Unknown') ?>"
                                        data-vmodel="<?= htmlspecialchars($row['model_name'] ?? 'N/A') ?>"
                                        data-vtype="<?= htmlspecialchars($row['type_name'] ?? 'N/A') ?>"
                                        data-fname="<?= htmlspecialchars($row['driver_fn']) ?>"
                                        data-lname="<?= htmlspecialchars($row['driver_ln']) ?>"
                                        data-status="<?= $isPaid ? 'Settled' : 'Unsettled' ?>">
                                        👁️ View
                                    </button>

                                    <?php if ($isPaid): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('<?= $row['tct_no'] ?>')">🗑️ Remove</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary opacity-50" disabled style="cursor: not-allowed;">🔒 Locked</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <nav class="mt-3">
            <ul class="pagination pagination-sm justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Citation Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-primary border-bottom pb-1">Driver Info</h6>
                        <small class="text-muted d-block">Full Name</small><p id="v_fullname" class="fw-bold"></p>
                        <small class="text-muted d-block">TCT Number</small><p id="v_tct" class="fw-bold"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary border-bottom pb-1">Vehicle Info</h6>
                        <small class="text-muted d-block">Plate Number</small><p id="v_plate" class="fw-bold"></p>
                        <small class="text-muted d-block">Model / Type</small><p id="v_vehicle" class="fw-bold"></p>
                    </div>
                    <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1">Violation Details</h6>
                        <div class="row">
                            <div class="col-4"><small class="text-muted d-block">Date/Time</small><p id="v_datetime"></p></div>
                            <div class="col-4"><small class="text-muted d-block">Officer</small><p id="v_officer"></p></div>
                            <div class="col-4"><small class="text-muted d-block">Payment Status</small><p id="v_status"></p></div>
                        </div>
                        <small class="text-muted d-block">Specific Offense(s)</small>
                        <div id="v_violation_list" class="p-2 bg-light border rounded mt-1"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// 1. Permanent Delete Function
function confirmDelete(tctNo) {
    Swal.fire({
        title: 'Delete Permanently?',
        text: "TCT #" + tctNo + " is settled. This will wipe it from the database forever.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Delete!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete_offense.php?tct_no=" + tctNo;
        }
    });
}

$(document).ready(function() {
    // 2. Consolidated View Handler
    $('.view-btn').on('click', function() {
        const d = $(this).data();

        $('#v_fullname').text(d.fname + ' ' + d.lname);
        $('#v_tct').text(d.tct);
        $('#v_plate').text(d.plate);
        $('#v_vehicle').text(d.vmodel + ' (' + d.vtype + ')');
        $('#v_datetime').text(d.date + ' ' + d.time);
        $('#v_officer').text(d.officername + ' (ID: ' + d.officerid + ')');

        const statusBadge = d.status === 'Settled'
            ? '<span class="badge bg-success">Settled</span>'
            : '<span class="badge bg-danger">Unsettled</span>';
        $('#v_status').html(statusBadge);

        $('#v_violation_list').html('<div class="spinner-border spinner-border-sm text-primary"></div> Loading...');

        $.getJSON('get_ticket_violations.php', { tct_no: d.tct })
        .done(function(data) {
            if(data && data.length > 0) {
                let html = '<ul class="mb-0">';
                data.forEach(v => { html += `<li>${v.violation_name}</li>`; });
                html += '</ul>';
                $('#v_violation_list').html(html);
            } else {
                $('#v_violation_list').text('No violations found.');
            }
        })
        .fail(function() {
            $('#v_violation_list').text('Error loading data.');
        });

        $('#viewModal').modal('show');
    });
});
</script>
</body>
</html>