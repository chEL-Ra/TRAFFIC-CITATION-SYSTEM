<?php 
require_once 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        // 1. Capture the data from POST
         $pdo->beginTransaction();
        // Note: These names must match the 'name' attribute in your HTML <input>
       $app_brgy = strtoupper($_POST['app_barangay'] ?? '');
       $app_muni = strtoupper($_POST['app_municipality'] ?? '');
       $app_prov = strtoupper($_POST['app_province'] ?? '');

        // 2. Prepare the SQL Statement
        // Double check: names here must match your phpMyAdmin columns EXACTLY
        $sql = "INSERT INTO citations (
                    tct_no, driver_ln, driver_fn, driver_mi,
                    license_type, license_no,
                    driver_brgy, driver_muni, driver_prov,
                    plate_no, vehicle_type, vehicle_model,
                    owner_ln, owner_fn, owner_mi, owner_contact,
                    owner_brgy, owner_muni, owner_prov,
                    date_apprehension, time_apprehension,
                    officer_id,
                    apprehension_barangay,
                    apprehension_municipality,
                    apprehension_province
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, 
                    ?, 
                    ?, ?, ?, 
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
    strtoupper($_POST['tct_no']),
    strtoupper($_POST['driver_ln']),
    strtoupper($_POST['driver_fn']),
    strtoupper($_POST['driver_mi']),
    strtoupper($_POST['license_type']),
    strtoupper($_POST['license_no']),
    strtoupper($_POST['driver_brgy']),
    strtoupper($_POST['driver_muni']),
    strtoupper($_POST['driver_prov']),
    strtoupper($_POST['plate_no']),
    $_POST['vehicle_type'],
    $_POST['vehicle_model'],
    strtoupper($_POST['owner_ln']),
    strtoupper($_POST['owner_fn']),
    strtoupper($_POST['owner_mi']),
    $_POST['owner_contact'], // keep numeric
    strtoupper($_POST['owner_brgy']),
    strtoupper($_POST['owner_muni']),
    strtoupper($_POST['owner_prov']),
    $_POST['date_apprehension'],
    $_POST['time_apprehension'],
    $_POST['officer_id'],
    $app_brgy,
    $app_muni,
    $app_prov
]);

         $id = $pdo->lastInsertId();

        // --- Insert Violations
      // --- 1. Insert into citations (including impoundable)
// First, determine if ANY of the violations were marked as impoundable
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

        echo json_encode([
            'status' => 'success',
            'message' => 'Record saved successfully!'
        ]);
        exit;

    } catch (Exception $e) {

        $pdo->rollBack();

        echo json_encode([
            'status' => 'error',
            'message' => 'DB Error: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Traffic Citation Ticket</title>
<style>
:root {
    --primary-red: #d62828;
    --bg-light: #f1f4f9;
    --dark-blue: #1d3557;
}

.header-title {
    color: var(--primary-red);
    text-align: center;
    letter-spacing: 2px;
    margin-bottom: 25px;
    font-weight: 800;
}

.flex-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    align-items: flex-start;
}

.column {
    flex: 1 1 32%;
    background: #fff;
    padding: 25px;
    border: 1px solid #ddd;
    border-radius: 12px;
    min-width: 320px;
    max-width: 100%;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

h3 { color: var(--dark-blue); border-bottom: 2px solid var(--primary-red); padding-bottom: 10px; margin-top: 0; }
h5 { margin: 0 0 10px 0; color: #888; text-transform: uppercase; font-size: 0.75em; letter-spacing: 1px; }

.section-separator { margin-top: 20px; border-top: 2px solid #eee; padding-top: 15px; }
.form-group { margin-bottom: 12px; }

label { display: inline-block; width: 140px; font-weight: bold; font-size: 0.85em; color: #555; }
input, select, textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; }
.full-input { width: 100%; }

.name-group { display: inline-flex; gap: 5px; }
.ln, .fn { width: 90px; } .mi { width: 35px; }

.input-row { display: flex; gap: 10px; }

.record-control-section {
    background: var(--dark-blue); color: #fff;
    padding: 25px; border-radius: 12px; margin-top: 25px; text-align: center;
}

.btn { padding: 12px 25px; font-weight: bold; cursor: pointer; border-radius: 6px; border: none; transition: 0.3s; text-transform: uppercase; }
.btn-save { background-color: #2a9d8f; color: white; }
.btn-refresh { background-color: #e63946; color: white; margin-right: 15px; }

#saveMessage { margin-top: 15px; padding: 12px 15px; border-radius: 8px; font-weight: bold; font-size: 1rem; display: inline-block; }
#saveMessage.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; animation: shake 0.3s; }

@keyframes shake { 0%{transform:translateX(0);}25%{transform:translateX(-5px);}50%{transform:translateX(5px);}75%{transform:translateX(-5px);}100%{transform:translateX(0);} }

.violation-row { position: relative; margin-bottom: 10px; padding-right: 30px; }
.violation-row button.remove-btn { position: absolute; top: 0; right: 0; background: none; border: none; color: red; font-size: 20px; cursor: pointer; }

/* Horizontal 3-column wrapper */
.place-horizontal-row { 
    display: flex; 
    gap: 10px; 
    flex-wrap: wrap; /* This allows fields to drop to next line if crowded */
    align-items: flex-start; 
    margin-top: 10px; 
}
.field-container { 
    flex: 1; 
    min-width: 120px; /* Ensures fields don't get too skinny */
    display: flex; 
    flex-direction: column;
}
.field-container label { font-weight:bold; font-size:0.75rem; color:#555; text-transform:uppercase; margin-bottom:5px; }
.field-container input { 
    width: 100%; 
    padding: 8px; 
    border: 1px solid #ccc; 
    border-radius: 4px; 
    box-sizing: border-box; /* Crucial: includes padding in the width calculation */
}
input, textarea {
    text-transform: uppercase;
}
</style>
</head>

<body>
<h1 class="header-title">TRAFFIC CITATION TICKET</h1>

<form id="mainForm">
<div class="flex-container">

<!-- DRIVER -->
<div class="column">
    <h3>Driver Section</h3>
  <div class="form-group">
    <label>TCT NO:</label>
    <input 
        type="text" 
        name="tct_no" 
        id="tct_no"
        class="full-input" 
        required 
        placeholder="000000"
        inputmode="numeric"
        pattern="[0-9]*"
        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
    >
</div>
    <div class="form-group">
        <label>Driver Name:</label>
        <div class="name-group">
            <input type="text" name="driver_ln" class="ln uppercase" placeholder="Last" required>
            <input type="text" name="driver_fn" class="fn uppercase" placeholder="First" required>
            <input type="text" name="driver_mi" class="mi uppercase" placeholder="M.I.">
        </div>
    </div>
    <div class="form-group">
        <label>License:</label>
        <select name="license_type" style="width:115px;">
            <option value="Non-Pro">Non-Pro</option>
            <option value="Pro">Professional</option>
            <option value="Student">Student</option>
        </select>
        <input type="text" name="license_no" style="width:120px;" placeholder="License No.">
    </div>

    <div class="section-separator">
        <h5>Driver Address</h5>
        <div class="place-horizontal-row">
            <div class="field-container"><label>Barangay:</label><input type="text" name="driver_brgy"></div>
            <div class="field-container"><label>Municipality:</label><input type="text" name="driver_muni"></div>
            <div class="field-container"><label>Province:</label><input type="text" name="driver_prov"></div>
        </div>
    </div>

    <div class="section-separator">
        <h5>Vehicle Info</h5>
        <div class="form-group"><label>Plate No:</label><input type="text" name="plate_no" class="full-input"></div>
        <div class="form-group">
            <label>Type / Brand / Model:</label>
            <div class="name-group">
                <select id="vehicle_type" name="vehicle_type" style="width:115px">
                    <option value="">Type</option>
                    <?php foreach($pdo->query("SELECT * FROM vehicle_types ORDER BY type_name ASC") as $t) echo "<option value='{$t['id']}'>{$t['type_name']}</option>"; ?>
                </select>
                <select id="vehicle_brand" name="vehicle_brand" style="width:115px" disabled><option value="">Brand</option></select>
                <select id="vehicle_model" name="vehicle_model" style="width:115px" disabled><option value="">Model</option></select>
            </div>
        </div>
    </div>
</div>

<!-- OWNER -->
<div class="column">
    <h3>Registered Owner</h3>
    <div class="form-group">
        <label>Owner Name:</label>
        <div class="name-group">
            <input type="text" name="owner_ln" class="ln" placeholder="Last">
            <input type="text" name="owner_fn" class="fn" placeholder="First">
            <input type="text" name="owner_mi" class="mi" placeholder="M.I.">
        </div>
    </div>
<div class="form-group">
    <label>Contact No:</label>
    <input type="text" 
           name="owner_contact" 
           class="full-input" 
           required 
           placeholder="09XXXXXXXXX"
           pattern="^09[0-9]{9}$"
           maxlength="11"
           inputmode="numeric"
           oninput="this.value = this.value.replace(/[^0-9]/g, '');"
           required>
    <small style="color:#e63946; font-weight:600;">
        Must start with 09 and contain exactly 11 digits.
    </small>
</div>

    <div class="section-separator">
        <h5>Owner Address</h5>
        <div class="place-horizontal-row">
            <div class="field-container"><label>Barangay:</label><input type="text" name="owner_brgy"></div>
            <div class="field-container"><label>Municipality:</label><input type="text" name="owner_muni"></div>
            <div class="field-container"><label>Province:</label><input type="text" name="owner_prov"></div>
        </div>
    </div>
</div>

<!-- APPREHENSION -->
<div class="column">
    <h3>Apprehension</h3>
    <div class="form-group">
        <label>Date & Time:</label>
        <input type="date" name="date_apprehension" value="<?= date('Y-m-d'); ?>" style="width:115px;">
        <input type="time" name="time_apprehension" value="<?= date('H:i'); ?>" style="width:110px;">
    </div>

    <div class="section-separator">
    <h5>Place of Apprehension</h5>
    <div class="place-horizontal-row">
        <div class="field-container">
            <label>Barangay:</label>
            <input type="text" name="app_barangay" placeholder="Barangay">
        </div>
        <div class="field-container">
            <label>Municipality:</label>
            <input type="text" name="app_municipality" placeholder="Municipality">
        </div>
        <div class="field-container">
            <label>Province:</label>
            <input type="text" name="app_province" placeholder="Province">
        </div>
    </div>
</div>

    <div class="section-separator"></div>
    <div class="form-group">
        <label>Officer ID:</label>
        <select name="officer_id" id="officer_id" class="full-input" required>
            <option value="">-- Select ID --</option>
            <?php foreach($pdo->query("SELECT id,name FROM officers ORDER BY id ASC") as $row) echo "<option value='{$row['id']}' data-name='{$row['name']}'>ID: {$row['id']}</option>"; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Officer Name:</label>
        <input type="text" id="officer_name" readonly class="full-input" style="background:#f8f9fa; font-weight:bold;">
    </div>

    <div class="section-separator">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:10px;">
            <h5 style="margin:0;">Violation Info</h5>
            <button type="button" id="addViolationBtn" style="background:#1d3557; color:white; border:none; border-radius:4px; cursor:pointer; padding: 4px 10px; font-size: 0.75em;">+ ADD VIOLATION</button>
        </div>

        <div id="violationContainer">
            <div class="violation-row">
                <div class="form-group">
                    <label>Violation:</label>
                    <select name="violations[]" class="full-input" required>
                        <option value="">-- Select Violation --</option>
                        <option value="Reckless Driving">Reckless Driving</option>
                        <option value="DUI">Driving Under Influence</option>
                        <option value="No License">No License</option>
                        <option value="Expired Registration">Expired Registration</option>
                        <option value="Unauthorized/improvised number plates">Unauthorized/improvised number plates</option>
                        <option value="Overspeeding">Overspeeding</option>
                        <option value="Illegal Parking">Illegal Parking</option>
                        <option value="Running Red Light">Running Red Light</option>
                        <option value="Using Mobile Phone While Driving">Using Mobile Phone While Driving</option>
                        <option value="Failure to Wear Seatbelt">Failure to Wear Seatbelt</option>
                        <option value="Overloading">Overloading</option>
                        <option value="Illegal U-Turn">Illegal U-Turn</option>
                        <option value="Expired Traffic Violation Receipt">Expired Traffic Violation Receipt</option>
                        <option value="Obstruction of Traffic">Obstruction of Traffic</option>
                        <option value="Illegal Use of Hazard Lights">Illegal Use of Hazard Lights</option>
                        <option value="Failure to Yield to Pedestrians">Failure to Yield to Pedestrians</option>
                        <option value="Illegal Use of Horn">Illegal Use of Horn</option>
                        <option value="Driving in the Wrong Lane">Driving in the Wrong Lane</option>
                        <option value="Failure to Use Turn Signals">Failure to Use Turn Signals</option>
                        <option value="Illegal Use of Headlights">Illegal Use of Headlights</option>
                        <option value="Installation of jalousies, curtains, dim colored lights, etc">Installation of jalousies, curtains, dim colored lights, etc</option>
                        <option value="Illegal Window Tinting">Illegal Window Tinting</option>
                        <option value="Illegal Use of Sirens">Illegal Use of Sirens</option>
                        <option value="Operating a motor vehicle with a suspended or revoked Certificate of Registration">Operating a motor vehicle with a suspended or revoked Certificate of Registration</option>
                        <option value="Operating a motor vehicle with a suspended or revoked driver's license">Operating a motor vehicle with a suspended or revoked driver's license</option>
                        <option value="Illegal Racing">Illegal Racing</option>
                        <option value="Using license plates different from the body number">Using license plates different from the body number</option>
                    </select>
                </div>
                <div class="form-group"><label>Others:</label>
                    <input type="text" name="other_violation[]" class="full-input">
                <label style="display:flex; align-items:center; gap:8px; color: var(--primary-red); cursor:pointer;">
                     <input type="checkbox" name="impoundable[]" value="1" style="width: 18px; height: 18px;">
                      <strong>Impoundable Vehicle</strong>
                </label>
            </div>
        </div>
    </div>
</div>

</div> <!-- flex-container -->

<div class="record-control-section">
    <button type="reset" class="btn btn-refresh">🔄 REFRESH FORM</button>
    <button type="button" id="saveBtn" class="btn btn-save">💾 SAVE RECORD</button>
</div>
</form>

<div id="saveMessage"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Element Selectors
    const form = document.getElementById('mainForm');
    const saveBtn = document.getElementById('saveBtn');
    const tctInput = document.getElementById('tct_no');
    const violationContainer = document.getElementById('violationContainer');
    const addBtn = document.getElementById('addViolationBtn');
    const officerSelect = document.getElementById('officer_id');
    const officerNameInput = document.getElementById('officer_name');
    const vehicleTypeSelect = document.getElementById('vehicle_type');
    const vehicleBrandSelect = document.getElementById('vehicle_brand');
    const vehicleModelSelect = document.getElementById('vehicle_model');

    // 2. Helper Functions
    function checkDuplicateViolations() {
        const selects = document.querySelectorAll('select[name="violations[]"]');
        const selectedValues = [];
        for (let select of selects) {
            if (select.value !== "") {
                if (selectedValues.includes(select.value)) {
                    Swal.fire('Duplicate', 'This violation has already been selected.', 'warning');
                    select.value = "";
                    return false;
                }
                selectedValues.push(select.value);
            }
        }
        return true;
    }

    function attachViolationListener(selectElement) {
        if (selectElement) {
            selectElement.addEventListener('change', checkDuplicateViolations);
        }
    }

    function showReviewStep() {
        Swal.fire({
            title: 'Review Record',
            text: "Are you sure you want to save this record?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(form);
                Swal.showLoading();
                fetch('save_record.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Saved!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Connection failed.', 'error'));
            }
        });
    }

    // 3. Dropdown & Row Logic
    if (officerSelect) {
        officerSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            officerNameInput.value = selected.getAttribute('data-name') || '';
        });
    }

    if (vehicleTypeSelect) {
        vehicleTypeSelect.addEventListener('change', function() {
            fetch(`get_brands.php?type_id=${this.value}`)
                .then(res => res.text())
                .then(data => {
                    vehicleBrandSelect.innerHTML = data;
                    vehicleBrandSelect.disabled = false;
                });
        });
    }

    if (vehicleBrandSelect) {
        vehicleBrandSelect.addEventListener('change', function() {
            fetch(`get_models.php?brand_id=${this.value}`)
                .then(res => res.text())
                .then(data => {
                    vehicleModelSelect.innerHTML = data;
                    vehicleModelSelect.disabled = false;
                });
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const firstRow = document.querySelector('.violation-row');
            const newRow = firstRow.cloneNode(true);
            newRow.querySelector('select').value = "";
            const removeBtn = document.createElement('button');
            removeBtn.type = "button";
            removeBtn.className = "remove-btn";
            removeBtn.innerHTML = "&times;";
            removeBtn.onclick = () => newRow.remove();
            newRow.appendChild(removeBtn);
            violationContainer.appendChild(newRow);
            attachViolationListener(newRow.querySelector('select[name="violations[]"]'));
        });
    }

    // 4. Save Logic
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            let allFilled = true;
            form.querySelectorAll('[required]').forEach(f => {
                if (!f.value.trim()) { 
                    allFilled = false; 
                    f.style.borderColor = 'red'; 
                } else {
                    f.style.borderColor = '#ccc';
                }
            });

            if (!allFilled) {
                Swal.fire('Incomplete', 'Please fill in all required fields.', 'warning');
                return;
            }

            // Uniqueness Check
            const tctValue = tctInput ? tctInput.value.trim() : '';
            fetch(`check_tct.php?tct_no=${tctValue}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        Swal.fire('Duplicate', 'TCT No. already exists!', 'error');
                    } else {
                        showReviewStep();
                    }
                })
                .catch(() => Swal.fire('Error', 'Duplicate check failed.', 'error'));
        });
    }

    // Initialize first row listener
    attachViolationListener(document.querySelector('select[name="violations[]"]'));

}); // <--- This closes the DOMContentLoaded block
</script>
</body>
</html>