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

       $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
       $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        
       $is_impoundable = !empty($_POST['impoundable']) ? 1 : 0;

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
                    apprehension_province,
                    latitude,
                    longitude,
                    impoundable,
                    other_violation
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
                    ?, ?,
                    ?,
                    ?,
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
    $app_prov,
    $latitude,
    $longitude,
    $is_impoundable,    
    strtoupper($_POST['other_violation'] ?? '')
]);

         $id = $pdo->lastInsertId();

      
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

 if (!empty($_POST['violations'])) {
            $tct_no = $_POST['tct_no'];
            $violationSQL = "INSERT INTO ticket_violations (tct_no, violation_name) VALUES (?, ?)";
            $violationStmt = $pdo->prepare($violationSQL);

            foreach ($_POST['violations'] as $violation) {
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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
.column.wider {
    flex: 1 1 100%;
    max-width: 100%;
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
.btn-map { background-color: #1d3557; color: white; font-size: 0.8em; padding: 8px 15px; }

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

#map {
    height: 350px;
    width: 100%;
    border-radius: 8px;
    border: 2px solid #ddd;
    margin-bottom: 15px;
}

.map-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.location-display {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.location-display .coord {
    font-weight: bold;
    color: var(--dark-blue);
}

.location-display .coord span {
    color: var(--primary-red);
}

/* Status indicator */
.location-status {
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 600;
}

.location-status.pending {
    background: #fff3cd;
    color: #856404;
}

.location-status.selected {
    background: #d4edda;
    color: #155724;
}
#other_vehicle_container {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    border: 1px dashed #ccc;
}

#other_vehicle_details {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    text-transform: uppercase; /* Keeps it consistent with your other fields */
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
                <option value="0">OTHERS</option> </select>
            <select id="vehicle_brand" name="vehicle_brand" style="width:115px" disabled><option value="">Brand</option></select>
            <select id="vehicle_model" name="vehicle_model" style="width:115px" disabled><option value="">Model</option></select>
        </div>
    </div>
</div>

<div id="other_vehicle_container" style="display: none;">
    <div class="section-separator">
        <h5 style="color: #d00000;">Specify Vehicle Details</h5>
        <div class="place-horizontal-row">
            <div class="field-container">
                <label>Manual Type</label>
                <input type="text" id="vehicle_type_other" name="vehicle_type_other" placeholder="e.g. E-Bike">
            </div>
            <div class="field-container">
                <label>Manual Brand</label>
                <input type="text" id="vehicle_brand_other" name="vehicle_brand_other" placeholder="e.g. NWOW">
            </div>
            <div class="field-container">
                <label>Manual Model</label>
                <input type="text" id="vehicle_model_other" name="vehicle_model_other" placeholder="e.g. ERV">
            </div>
        </div>
    </div>
</div><div id="other_vehicle_container" style="display: none;">
    <div class="section-separator">
        <h5 style="color: #d00000;">Specify Vehicle Details</h5>
        <div class="place-horizontal-row">
            <div class="field-container">
                <label>Manual Type</label>
                <input type="text" name="vehicle_type_other" id="vehicle_type_other" placeholder="e.g. E-Bike">
            </div>
            <div class="field-container">
                <label>Manual Brand</label>
                <input type="text" name="vehicle_brand_other" id="vehicle_brand_other" placeholder="e.g. NWOW">
            </div>
            <div class="field-container">
                <label>Manual Model</label>
                <input type="text" name="vehicle_model_other" id="vehicle_model_other" placeholder="e.g. ERV">
            </div>
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
        <input type="text" id="app_barangay" name="app_barangay" placeholder="Barangay">
    </div>
    <div class="field-container">
        <label>Municipality:</label>
        <input type="text" id="app_municipality" name="app_municipality" placeholder="Municipality">
    </div>
    <div class="field-container">
        <label>Province:</label>
        <input type="text" id="app_province" name="app_province" placeholder="Province">
    </div>
</div>
<div class="form-group" style="margin-top:10px;">
    <label>Street/Sitio:</label>
    <input type="text" id="app_street" name="app_street" class="full-input" placeholder="e.g. 123 Main St">
</div>

<div class="column wide">
        <h3>📍 Apprehension Location (Map)</h3>
        
        <div class="map-controls">
            <button type="button" id="getLocationBtn" class="btn btn-map">
                📍 Use My Current Location
            </button>
            <button type="button" id="clearMapBtn" class="btn btn-map" style="background: #6c757d;
        ">🗑️ Clear Map</button>
        </div>

        <div id="map"></div>

        <!-- Hidden fields for coordinates -->
        <input type="hidden" id="latitude" name="latitude" value="">
        <input type="hidden" id="longitude" name="longitude" value="">

        <!-- Location Display -->
        <div class="location-display">
            <div class="location-status pending" id="locationStatus">
                ⚠️ Click on the map to select location
            </div>
            <div class="coord">
                Latitude: <span id="latDisplay">-</span>
            </div>
            <div class="coord">
                Longitude: <span id="lngDisplay">-</span>
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
                        <option value="DTO POLICE / TRAFFIC ENFORCER">DTO POLICE / TRAFFIC ENFORCER</option>
                        <option value="DTS (TRAFFIC DEVICES AND SIGNALS)">DTS (TRAFFIC DEVICES AND SIGNALS)</option>
                        <option value="DTS (NO L-TURN, NO R-TURN, NO U-TURN, NO STOPPING, NO PARKING, NO LOADING & UNLOADING)">DTS (NO L-TURN, NO R-TURN, NO U-TURN, NO STOPPING, NO PARKING, NO LOADING & UNLOADING)</option>
                        <option value="OVERSPEEDING">OVERSPEEDING</option>
                        <option value="OBSTRUCTION (SIDEWALK, DRIVEWAY)">OBSTRUCTION (SIDEWALK, DRIVEWAY)</option>
                        <option value="OBSTRUCTION OF TRAFFIC">OBSTRUCTION OF TRAFFIC</option>
                        <option value="SELLING ON PUBLIC STREETS / SIDEWALKS">SELLING ON PUBLIC STREETS / SIDEWALKS</option>
                        <option value="NO PARKING ON EXPRESSWAYS AND HIGHWAYS">NO PARKING ON EXPRESSWAYS AND HIGHWAYS</option>
                        <option value="TRUCKBAN (4,500 AND UP)">TRUCKBAN (4,500 AND UP)</option>
                        <option value="DRIVING MV UNDER THE INFLUENCE OF ALCOHOL, DANGEROUS DRUGS">DRIVING MV UNDER THE INFLUENCE OF ALCOHOL, DANGEROUS DRUGS</option>
                        <option value="RECKLESS DRIVING">RECKLESS DRIVING</option>
                        <option value="FAILURE TO WEAR PRESCRIBED, STANDARD HELMET (DRIVER AND BACKRIDER)">FAILURE TO WEAR PRESCRIBED, STANDARD HELMET (DRIVER AND BACKRIDER)</option>
                        <option value="MV W/O OR DEFECTIVE-IMPROPER UNAUTHORIZED ACCESSORIES, DEVICES EQUIPMENT AND PARTS">MV W/O OR DEFECTIVE-IMPROPER UNAUTHORIZED ACCESSORIES, DEVICES EQUIPMENT AND PARTS</option>
                        <option value="OVERLOADING OF CARGOS">OVERLOADING OF CARGOS</option>
                        <option value="HITCHING">HITCHING</option>
                        <option value="ILLEGAL TERMINAL UNAUTHORIZED PARKING AREA">ILLEGAL TERMINAL UNAUTHORIZED PARKING AREA</option>
                        <option value="OPERATING OUTSIDE THE LINE">OPERATING OUTSIDE THE LINE</option>
                        <option value="CUTTING TRIP">CUTTING TRIP</option>
                        <option value="NOT CONVEYING PASSENGERS IN THEIR DESTINATION-REFUSAL TO CONVEY PASSENGER">NOT CONVEYING PASSENGERS IN THEIR DESTINATION-REFUSAL TO CONVEY PASSENGER</option>
                        <option value="NOT FOLLOWING ROUTE">NOT FOLLOWING ROUTE</option>
                        <option value="OVERCHARGING (PUJ)">OVERCHARGING (PUJ)</option>
                        <option value="PASSENGER ON TOP OF THE MOVING VEHICLE">PASSENGER ON TOP OF THE MOVING VEHICLE</option>
                        <option value="WEARING SLIPPERS">WEARING SLIPPERS</option>
                        <option value="WEARING SHORTS PANTS">WEARING SHORTS PANTS</option>
                        <option value="DRIVING W/O VALID DRIVERS LICENSE">DRIVING W/O VALID DRIVERS LICENSE</option>
                        <option value="FAILURE TO CARRY DL, OR-CR WHILE DRIVING">FAILURE TO CARRY DL, OR-CR WHILE DRIVING</option>
                        <option value="DRIVING AN UNREGISTERED-DELINQUENT MV">DRIVING AN UNREGISTERED-DELINQUENT MV</option>
                        <option value="UNAUTHORIZED MV MODIFICATION (BODY, COLOR & CHASIS)">UNAUTHORIZED MV MODIFICATION (BODY, COLOR & CHASIS)</option>
                        <option value="FRAUD IN RELATION TO MV REGISTRATION">FRAUD IN RELATION TO MV REGISTRATION</option>
                        <option value="COLORUM (OPERATING W/O FRANCHISE)">COLORUM (OPERATING W/O FRANCHISE)</option>
                        <option value="NO PLATE ATTACHED">NO PLATE ATTACHED</option>
                        <option value="OTHER VIOLATIONS">OTHER VIOLATIONS</option>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

     // ========== MAP INITIALIZATION ==========
    let map;
    let marker;
    let currentLat = null;
    let currentLng = null;

    // Default center (Philippines)
    const defaultLat = 12.8797;
    const defaultLng = 121.7740;
    const defaultZoom = 6;

    function initMap() {
        map = L.map('map', {
            center: [defaultLat, defaultLng],
            zoom: defaultZoom,
            zoomControl: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Add click event
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            setMarker(lat, lng);
        });
    }


    function setMarker(lat, lng) {
        currentLat = lat;
        currentLng = lng;

        // Update hidden fields
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;

        // Update display
        document.getElementById('latDisplay').textContent = lat.toFixed(6);
        document.getElementById('lngDisplay').textContent = lng.toFixed(6);

        // Update status
        const statusEl = document.getElementById('locationStatus');
        statusEl.className = 'location-status selected';
        statusEl.innerHTML = '✅ Location selected';

        // Add or move marker
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                currentLat = pos.lat;
                currentLng = pos.lng;
                document.getElementById('latitude').value = pos.lat;
                document.getElementById('longitude').value = pos.lng;
                document.getElementById('latDisplay').textContent = pos.lat.toFixed(6);
                document.getElementById('lngDisplay').textContent = pos.lng.toFixed(6);
            });
        }

        // Get address from coordinates (reverse geocoding)
        reverseGeocode(lat, lng);
    }

    // Function to search address and move map
function updateMapFromFields() {
    const street = document.getElementById('app_street').value;
    const brgy = document.getElementById('app_barangay').value;
    const muni = document.getElementById('app_municipality').value;
    const prov = document.getElementById('app_province').value;

    if (!brgy && !muni) return; // Don't search if fields are empty

    const fullAddress = `${street} ${brgy} ${muni} ${prov} Philippines`;
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&limit=1`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                
                // Move map and marker
                map.setView([lat, lon], 16);
                setMarker(lat, lon);
                
                // Update coordinate displays
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lon;
                document.getElementById('latDisplay').textContent = lat.toFixed(6);
                document.getElementById('lngDisplay').textContent = lon.toFixed(6);
            }
        })
        .catch(error => console.error('Error fetching address:', error));
}

// Debounce helper to prevent hitting the API too hard
function debounce(func, timeout = 500) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

const processChange = debounce(() => updateMapFromFields());

// Attach listeners to fields
['app_street', 'app_barangay', 'app_municipality', 'app_province'].forEach(id => {
    document.getElementById(id).addEventListener('input', processChange);
});
    function reverseGeocode(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.address) {
                    const addr = data.address;
                    
                    // Auto-fill barangay
                    if (addr.barangay || addr.suburb) {
                        document.getElementById('app_barangay').value = (addr.barangay || addr.suburb || '').toUpperCase();
                    }
                    
                    // Auto-fill municipality/city
                    if (addr.city || addr.municipality || addr.town) {
                        document.getElementById('app_municipality').value = (addr.city || addr.municipality || addr.town || '').toUpperCase();
                    }
                    
                    // Auto-fill province
                    if (addr.province || addr.state) {
                        document.getElementById('app_province').value = (addr.province || addr.state || '').toUpperCase();
                    }
                }
            })
            .catch(error => console.error('Geocoding error:', error));
    }

    function clearMap() {
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        currentLat = null;
        currentLng = null;
        
        document.getElementById('latitude').value = '';
        document.getElementById('longitude').value = '';
        document.getElementById('latDisplay').textContent = '-';
        document.getElementById('lngDisplay').textContent = '-';
        document.getElementById('app_barangay').value = '';
        document.getElementById('app_municipality').value = '';
        document.getElementById('app_province').value = '';
        
        const statusEl = document.getElementById('locationStatus');
        statusEl.className = 'location-status pending';
        statusEl.innerHTML = '⚠️ Click on the map to select location';
    }

   function getCurrentLocation() {
    if (navigator.geolocation) {
        Swal.fire({
            title: 'Pinpointing Location...',
            text: 'Acquiring high-accuracy coordinates.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                Swal.close();
                
                // Increase zoom to 18 for a tighter view of your actual spot
                map.setView([lat, lng], 18); 
                setMarker(lat, lng);
            },
            function(error) {
                Swal.close();
                let msg = 'Error: ';
                if (error.code === 1) msg += 'Permission denied. Please enable GPS.';
                else if (error.code === 2) msg += 'Position unavailable.';
                else if (error.code === 3) msg += 'Request timed out.';
                Swal.fire('Location Error', msg, 'error');
            },
            { 
                enableHighAccuracy: true, // Forces the device to use GPS hardware
                timeout: 10000,           // Wait up to 10 seconds for a lock
                maximumAge: 0             // Do NOT use a cached (old) location
            }
        );
    } else {
        Swal.fire('Error', 'Geolocation is not supported by your browser.', 'error');
    }
}
    // Initialize map
    initMap();

    // Map button events
    document.getElementById('getLocationBtn').addEventListener('click', getCurrentLocation);
    document.getElementById('clearMapBtn').addEventListener('click', clearMap);
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

    selects.forEach(select => {
        if (select.value !== "") {
            if (selectedValues.includes(select.value)) {
                // Show the alert
                Swal.fire({
                    title: 'Duplicate Violation',
                    text: 'This violation has already been selected for this ticket.',
                    icon: 'warning',
                    confirmButtonColor: '#1d3557'
                });
                // Reset the duplicate dropdown
                select.value = "";
            } else {
                selectedValues.push(select.value);
            }
        }
    });
}

// Inside your DOMContentLoaded listener
const otherContainer = document.getElementById('other_vehicle_container');
const otherInput = document.getElementById('other_vehicle_details');

// Function to toggle visibility
function toggleOtherField() {
    const isOtherType = vehicleTypeSelect.value === "0" || vehicleTypeSelect.value === "OTHERS";
    const isOtherBrand = vehicleBrandSelect.value === "0" || vehicleBrandSelect.value === "OTHERS";
    const isOtherModel = vehicleModelSelect.value === "0" || vehicleModelSelect.value === "OTHERS";

    // Show container if ANY of them are "Others"
    if (isOtherType || isOtherBrand || isOtherModel) {
        otherContainer.style.display = 'block';
    } else {
        otherContainer.style.display = 'none';
        // Clear values if hidden to avoid accidental data
        document.getElementsByName('vehicle_type_other')[0].value = "";
        document.getElementsByName('vehicle_brand_other')[0].value = "";
        document.getElementsByName('vehicle_model_other')[0].value = "";
    }
}
[vehicleTypeSelect, vehicleBrandSelect, vehicleModelSelect].forEach(select => {
    if (select) {
        select.addEventListener('change', toggleOtherField);
    }
});
// Attach to your existing change listeners
if (vehicleTypeSelect) {
    vehicleTypeSelect.addEventListener('change', function() {
        fetch(`get_brands.php?type_id=${this.value}`)
            .then(res => res.text())
            .then(data => {
                vehicleBrandSelect.innerHTML = data;
                vehicleBrandSelect.disabled = false;
                toggleOtherField(); // Re-check after list updates
            });
    });
}

// Also check Brand/Model if "Others" can appear there
[vehicleBrandSelect, vehicleModelSelect].forEach(select => {
    if (select) {
        select.addEventListener('change', function() {
            if (this.value === "0" || this.value === "OTHERS") {
                toggleOtherField(this.value);
            }
        });
    }
});
// Monitor the container for any changes in violation dropdowns
if (violationContainer) {
    violationContainer.addEventListener('change', function(e) {
        // Check if the element changed was actually a violation dropdown
        if (e.target && e.target.name === 'violations[]') {
            checkDuplicateViolations();
        }
    });
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
        if (!firstRow) return;

        const newRow = firstRow.cloneNode(true);
        
        // Clear the value of the cloned dropdown
        const newSelect = newRow.querySelector('select');
        if (newSelect) newSelect.value = "";

        // Add the remove button logic
        const removeBtn = document.createElement('button');
        removeBtn.type = "button";
        removeBtn.className = "remove-btn"; // Make sure this class is in your CSS
        removeBtn.innerHTML = "&times;";
        removeBtn.onclick = () => {
            newRow.remove();
            checkDuplicateViolations(); // Re-check duplicates after removal
        };

        newRow.appendChild(removeBtn);
        violationContainer.appendChild(newRow);
    });
}

    // 4. Save Logic
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            let allFilled = true;
            // Check required fields
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

            // Uniqueness Check for TCT
            const tctValue = tctInput ? tctInput.value.trim() : '';
            fetch(`check_tct.php?tct_no=${tctValue}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        Swal.fire('Duplicate', 'TCT No. already exists!', 'error');
                    } else {
                        showReviewStep(); // This calls the function that actually saves via fetch
                    }
                })
                .catch(err => {
                    console.error("Error checking TCT:", err);
                    // If check_tct.php doesn't exist yet, we'll proceed for testing
                    showReviewStep(); 
                });
        });
    }
}); // End of DOMContentLoaded
</script>
</body>
</html>