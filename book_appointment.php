<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id      = $_SESSION['user_id'];
$user_name    = $_SESSION['user_name'];
$message      = "";
$message_type = "";

/* ── Get patient ── */
$ps = $conn->prepare("SELECT id, name FROM patients WHERE user_id = ? LIMIT 1");
$ps->bind_param("i", $user_id);
$ps->execute();
$patient_result = $ps->get_result();

if ($patient_result->num_rows === 0) {
    die("Patient profile not found. Please contact support.");
}

$patient    = $patient_result->fetch_assoc();
$patient_id = $patient['id'];

/* ── Handle POST ── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $doctor_id        = intval($_POST['doctor_id']);
    $appointment_date = trim($_POST['appointment_date']);
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $reason           = trim($_POST['reason'] ?? '');

    // Resolve appointment_time from hour, minute, and AM/PM if direct value is empty or needs normalization
    if (empty($appointment_time) && !empty($_POST['time_hour']) && isset($_POST['time_minute']) && !empty($_POST['time_ampm'])) {
        $th = intval($_POST['time_hour']);
        $tm = intval($_POST['time_minute']);
        $tap = strtoupper(trim($_POST['time_ampm']));
        if ($tap === 'PM' && $th < 12) $th += 12;
        if ($tap === 'AM' && $th === 12) $th = 0;
        $appointment_time = sprintf('%02d:%02d:00', $th, $tm);
    } elseif (!empty($appointment_time)) {
        $parsed_ts = strtotime($appointment_time);
        if ($parsed_ts !== false) {
            $appointment_time = date('H:i:00', $parsed_ts);
        }
    }

    if ($doctor_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
        $message      = "Please fill all required fields.";
        $message_type = "danger";
    } elseif ($appointment_date < date("Y-m-d")) {
        $message      = "Please select today or a future date.";
        $message_type = "danger";
    } else {
        /* Check doctor exists */
        $dc = $conn->prepare("SELECT id FROM doctors WHERE id = ? LIMIT 1");
        $dc->bind_param("i", $doctor_id);
        $dc->execute();

        if ($dc->get_result()->num_rows === 0) {
            $message      = "Selected doctor does not exist.";
            $message_type = "danger";
        } else {
            /* Check duplicate slot */
            $ck = $conn->prepare(
                "SELECT id FROM appointments
                 WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
                 AND status != 'Cancelled' LIMIT 1"
            );
            $ck->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
            $ck->execute();

            if ($ck->get_result()->num_rows > 0) {
                $message      = "Doctor is busy in this slot. Please choose a different time.";
                $message_type = "warning";
            } else {
                $ins = $conn->prepare(
                    "INSERT INTO appointments
                     (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                     VALUES (?, ?, ?, ?, ?, 'Pending')"
                );
                $ins->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);

                if ($ins->execute()) {
                    $message      = "Appointment booked successfully! Status is Pending until approved.";
                    $message_type = "success";
                } else {
                    $message      = "Something went wrong. Please try again.";
                    $message_type = "danger";
                }
            }
        }
    }
}

/* ── Load departments and doctors ── */
$dept_query = $conn->query("SELECT id, name, code, icon FROM departments ORDER BY name ASC");
$departments_data = $dept_query ? $dept_query->fetch_all(MYSQLI_ASSOC) : [];

$doctors_result = $conn->query(
    "SELECT d.id, d.doctor_code, d.name, d.specialization, d.experience, d.department_id, dep.name AS department_name, dep.icon AS department_icon
     FROM doctors d
     LEFT JOIN departments dep ON d.department_id = dep.id
     ORDER BY dep.name ASC, d.name ASC"
);

$avatar_letter = strtoupper(substr($user_name, 0, 1));

// Initialize default time values (Hour, Minute, AM/PM)
$selected_hour   = '09';
$selected_minute = '00';
$selected_ampm   = 'AM';

if (!empty($_POST['appointment_time'])) {
    $parsed_ts = strtotime($_POST['appointment_time']);
    if ($parsed_ts !== false) {
        $selected_hour   = date('h', $parsed_ts);
        $selected_minute = date('i', $parsed_ts);
        $selected_ampm   = date('A', $parsed_ts);
    }
} elseif (!empty($_POST['time_hour']) && isset($_POST['time_minute']) && !empty($_POST['time_ampm'])) {
    $selected_hour   = sprintf('%02d', intval($_POST['time_hour']));
    $selected_minute = sprintf('%02d', intval($_POST['time_minute']));
    $selected_ampm   = strtoupper(trim($_POST['time_ampm']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | MediCare Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dash-main { position: relative; z-index: 1; padding: 40px 0 70px; }

        .appt-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .appt-header {
            background: linear-gradient(135deg, rgba(37,99,235,0.2), rgba(6,182,212,0.1));
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 36px 40px;
            text-align: center;
        }

        .appt-icon {
            width: 72px; height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
            margin-bottom: 16px;
            box-shadow: 0 12px 32px rgba(37,99,235,0.4);
        }

        .appt-body { padding: 40px; }

        .field-section {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-dim);
            margin: 28px 0 18px;
        }
        .field-section::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.07);
        }

        .doctor-option-card {
            display: none;
            background: rgba(37,99,235,0.08);
            border: 1px solid rgba(37,99,235,0.25);
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--text-muted);
            transition: all 0.3s;
        }

        .success-overlay {
            text-align: center;
            padding: 20px 0 10px;
        }

        .success-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(16,185,129,0.15);
            border: 2px solid rgba(16,185,129,0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #34d399;
            margin-bottom: 16px;
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .time-preview-pill {
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(37, 99, 235, 0.35);
            color: var(--primary-light, #60a5fa);
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        .time-picker-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.15);
        }

        .time-picker-item {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .time-picker-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-dim, #94a3b8);
            letter-spacing: 0.8px;
            margin-bottom: 5px;
            text-align: center;
        }

        .time-picker-select {
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 6px !important;
            font-size: 14px !important;
            border-radius: 10px !important;
        }

        .time-separator {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-light, #60a5fa);
            padding-top: 14px;
            user-select: none;
        }

        .ampm-toggle-wrap {
            flex: 1.15;
        }

        .ampm-btn-group .btn {
            border-color: rgba(37, 99, 235, 0.35);
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 4px;
            transition: all 0.2s ease;
        }

        .ampm-btn-group .btn:hover {
            color: #fff;
            border-color: rgba(37, 99, 235, 0.7);
        }

        .ampm-btn-group .btn-check:checked + .btn {
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            border-color: transparent !important;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar navbar-expand-lg dash-navbar">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-hospital-fill" style="-webkit-text-fill-color:var(--primary-light);"></i>
            <span style="background:linear-gradient(135deg,#fff,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">MediCare</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="dash-main">
<div class="container">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4" style="animation: fadeInUp 0.4s ease both;">
        <ol class="breadcrumb" style="background:transparent; padding:0; font-size:13px;">
            <li class="breadcrumb-item">
                <a href="dashboard.php" style="color:var(--primary-light); text-decoration:none;">
                    <i class="bi bi-house me-1"></i>Dashboard
                </a>
            </li>
            <li class="breadcrumb-item active" style="color:var(--text-muted);">Book Appointment</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="appt-card" style="animation: fadeInUp 0.5s 0.1s ease both;">

                <div class="appt-header">
                    <div class="appt-icon"><i class="bi bi-calendar2-plus"></i></div>
                    <h3 class="fw-bold mb-1" style="color:#fff; letter-spacing:-0.5px;">Book an Appointment</h3>
                    <p style="color:var(--text-muted); font-size:14px; margin:0;">
                        Select your doctor and preferred time slot
                    </p>
                </div>

                <div class="appt-body">

                    <!-- ── Alert Messages ── -->
                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                        <i class="bi bi-<?php
                            echo match($message_type) {
                                'success' => 'check-circle-fill',
                                'warning' => 'exclamation-triangle-fill',
                                default   => 'x-circle-fill'
                            };
                        ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <?php if ($message_type === 'success'): ?>
                    <div class="success-overlay">
                        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                        <h5 class="fw-bold" style="color:#fff;">Appointment Confirmed!</h5>
                        <p style="color:var(--text-muted); font-size:14px;">
                            Your appointment request has been submitted and is awaiting doctor approval.
                        </p>
                        <div class="d-flex gap-3 justify-content-center mt-3">
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                            <a href="book_appointment.php" class="btn btn-primary px-4">
                                <i class="bi bi-calendar-plus me-1"></i>Book Another
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- ── Form ── -->
                    <?php if ($message_type !== 'success'): ?>
                    <form method="POST" action="">

                        <!-- Patient (readonly) -->
                        <div class="field-section"><i class="bi bi-person-fill"></i> Patient</div>
                        <div class="mb-4">
                            <label class="form-label">Patient Name</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-right:none;color:#475569;">
                                    <i class="bi bi-person-check"></i>
                                </span>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($patient['name']); ?>"
                                       readonly style="border-left:none;">
                            </div>
                        </div>

                        <!-- Doctor & Department -->
                        <div class="field-section"><i class="bi bi-person-badge"></i> Department & Doctor</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label">Hospital Department</label>
                                <select id="departmentFilter" class="form-select" onchange="filterDoctorsByDept(this.value)">
                                    <option value="all">— All Departments —</option>
                                    <?php foreach ($departments_data as $dep): ?>
                                    <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Select Doctor <span style="color:#f87171;">*</span></label>
                                <select name="doctor_id" id="doctorSelect" class="form-select" required
                                        onchange="showDoctorInfo(this)">
                                    <option value="">— Choose a Doctor —</option>
                                    <?php
                                    $doctors_data = [];
                                    while ($doc = $doctors_result->fetch_assoc()) {
                                        $doctors_data[] = $doc;
                                        $selected = (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doc['id']) ? 'selected' : '';
                                        $label = htmlspecialchars($doc['name']);
                                        if (!empty($doc['specialization'])) {
                                            $label .= ' — ' . htmlspecialchars($doc['specialization']);
                                        }
                                        $deptIdAttr = $doc['department_id'] ?? 'none';
                                        echo "<option value=\"{$doc['id']}\" data-dept=\"$deptIdAttr\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <!-- Doctor info card -->
                            <div class="col-12">
                                <div id="doctorInfoCard" class="doctor-option-card" style="margin-top:0;">
                                    <i class="bi bi-person-badge text-primary me-2"></i>
                                    <span id="docInfoText"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="field-section"><i class="bi bi-clock"></i> Schedule</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Appointment Date <span style="color:#f87171;">*</span></label>
                                <input type="date" name="appointment_date" class="form-control" required
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">Appointment Time <span style="color:#f87171;">*</span></label>
                                    <span class="badge time-preview-pill" id="timePreview">
                                        <i class="bi bi-clock-fill me-1"></i><span id="selectedTimeText"><?= htmlspecialchars($selected_hour . ':' . $selected_minute . ' ' . $selected_ampm) ?></span>
                                    </span>
                                </div>
                                <div class="time-picker-box">
                                    <!-- Hour -->
                                    <div class="time-picker-item">
                                        <label class="time-picker-label">Hour</label>
                                        <select id="timeHour" name="time_hour" class="form-select time-picker-select" aria-label="Hour">
                                            <?php for ($h = 1; $h <= 12; $h++): 
                                                $h_val = sprintf('%02d', $h);
                                            ?>
                                                <option value="<?= $h_val ?>" <?= $selected_hour === $h_val ? 'selected' : '' ?>><?= $h_val ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="time-separator">:</div>

                                    <!-- Minute -->
                                    <div class="time-picker-item">
                                        <label class="time-picker-label">Minute</label>
                                        <select id="timeMinute" name="time_minute" class="form-select time-picker-select" aria-label="Minute">
                                            <?php for ($m = 0; $m < 60; $m++): 
                                                $m_val = sprintf('%02d', $m);
                                            ?>
                                                <option value="<?= $m_val ?>" <?= $selected_minute === $m_val ? 'selected' : '' ?>><?= $m_val ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <!-- AM/PM Selector -->
                                    <div class="time-picker-item ampm-toggle-wrap">
                                        <label class="time-picker-label">Period</label>
                                        <div class="btn-group w-100 ampm-btn-group" role="group">
                                            <input type="radio" class="btn-check" name="time_ampm" id="ampm_am" value="AM" <?= $selected_ampm === 'AM' ? 'checked' : '' ?> autocomplete="off">
                                            <label class="btn btn-outline-primary" for="ampm_am">AM</label>

                                            <input type="radio" class="btn-check" name="time_ampm" id="ampm_pm" value="PM" <?= $selected_ampm === 'PM' ? 'checked' : '' ?> autocomplete="off">
                                            <label class="btn btn-outline-primary" for="ampm_pm">PM</label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden input carrying standardized 24-hr time for DB -->
                                <input type="hidden" name="appointment_time" id="appointment_time" value="<?= htmlspecialchars(($appointment_time ?? '09:00:00')) ?>">
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="field-section"><i class="bi bi-chat-text"></i> Reason</div>
                        <div class="mb-5">
                            <label class="form-label">Reason / Chief Complaint</label>
                            <textarea name="reason" class="form-control" rows="4"
                                      placeholder="Describe your symptoms or reason for this appointment..."><?php
                                echo htmlspecialchars($_POST['reason'] ?? '');
                            ?></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-3">
                            <a href="dashboard.php" class="btn btn-outline-secondary flex-fill py-3">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary flex-fill py-3" style="font-size:15px;">
                                <i class="bi bi-calendar-check me-2"></i>Book Appointment
                            </button>
                        </div>

                    </form>
                    <?php endif; ?>

                </div><!-- /appt-body -->
            </div><!-- /appt-card -->
        </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const doctorsData = <?php echo json_encode($doctors_data); ?>;

function filterDoctorsByDept(deptId) {
    const sel = document.getElementById('doctorSelect');
    const opts = sel.querySelectorAll('option');
    let hasMatch = false;

    opts.forEach(opt => {
        if (!opt.value) return; // Keep choose option
        const optDept = opt.getAttribute('data-dept');
        if (deptId === 'all' || optDept === deptId) {
            opt.style.display = '';
            hasMatch = true;
        } else {
            opt.style.display = 'none';
        }
    });

    // Reset selection if currently selected doctor does not match department
    const currentSelected = sel.selectedOptions[0];
    if (currentSelected && currentSelected.value && currentSelected.style.display === 'none') {
        sel.value = '';
        document.getElementById('doctorInfoCard').style.display = 'none';
    }
}

function showDoctorInfo(select) {
    const val  = parseInt(select.value);
    const card = document.getElementById('doctorInfoCard');
    const text = document.getElementById('docInfoText');

    if (!val) { card.style.display = 'none'; return; }

    const doc = doctorsData.find(d => d.id == val);
    if (doc) {
        let info = `<strong style="color:#fff;">${doc.name}</strong>`;
        if (doc.specialization) info += ` &bull; ${doc.specialization}`;
        if (doc.experience)     info += ` &bull; ${doc.experience} yrs experience`;
        text.innerHTML = info;
        card.style.display = 'block';
    }
}

// Sync custom styled time picker with hidden input & badge
function syncAppointmentTime() {
    const hourEl = document.getElementById('timeHour');
    const minEl = document.getElementById('timeMinute');
    const ampmChecked = document.querySelector('input[name="time_ampm"]:checked');
    const previewText = document.getElementById('selectedTimeText');
    const hiddenInput = document.getElementById('appointment_time');

    if (!hourEl || !minEl || !ampmChecked || !hiddenInput) return;

    let hour = parseInt(hourEl.value, 10);
    const minute = minEl.value;
    const ampm = ampmChecked.value;

    if (previewText) {
        previewText.textContent = `${hourEl.value}:${minute} ${ampm}`;
    }

    if (ampm === 'PM' && hour < 12) hour += 12;
    if (ampm === 'AM' && hour === 12) hour = 0;

    hiddenInput.value = `${String(hour).padStart(2, '0')}:${minute}:00`;
}

// Bind time picker events
const timeHour = document.getElementById('timeHour');
const timeMinute = document.getElementById('timeMinute');
const ampmRadios = document.querySelectorAll('input[name="time_ampm"]');

if (timeHour) timeHour.addEventListener('change', syncAppointmentTime);
if (timeMinute) timeMinute.addEventListener('change', syncAppointmentTime);
ampmRadios.forEach(r => r.addEventListener('change', syncAppointmentTime));

// Trigger on page load if doctor was already selected
window.addEventListener('load', () => {
    const sel = document.getElementById('doctorSelect');
    if (sel && sel.value) showDoctorInfo(sel);
    syncAppointmentTime();
});
</script>
</body>
</html>
