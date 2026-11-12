<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = ""; $message_type = "";

// Get doctor details
$ds = $conn->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
$ds->bind_param("i", $user_id);
$ds->execute();
$doctor = $ds->get_result()->fetch_assoc();
$doctor_id = $doctor['id'];

// Handle Prescription Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    $patient_id = intval($_POST['patient_id']);
    $diagnosis = trim($_POST['diagnosis']);
    $medicines = trim($_POST['medicines']);
    $instructions = trim($_POST['instructions']);
    
    if (empty($patient_id) || empty($medicines)) {
        $message = "Patient and medicines are required.";
        $message_type = "danger";
    } else {
        $ins = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, diagnosis, medicines, instructions) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("iisss", $patient_id, $doctor_id, $diagnosis, $medicines, $instructions);
        if ($ins->execute()) {
            
            // Send directly to Pharmacy
            $pharm_status = 'Pending';
            $pharm_qty = 1;
            $pharm_notes = "Dr. Diagnosis: " . $diagnosis . " | Instr: " . $instructions;
            
            $med_list = array_map('trim', explode(',', $medicines));
            $pref = $conn->prepare("INSERT INTO pharmacy_requests (patient_id, medicine_name, quantity, notes, status) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($med_list as $med) {
                if (!empty($med)) {
                    $pharm_meds = substr($med, 0, 250);
                    $pref->bind_param("isiss", $patient_id, $pharm_meds, $pharm_qty, $pharm_notes, $pharm_status);
                    $pref->execute();
                }
            }
            
            $message = "Prescription added and sent to Pharmacy successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to add prescription.";
            $message_type = "danger";
        }
    }
}

// Fetch all patients for the doctor to prescribe
$ps = $conn->prepare("SELECT id, name FROM patients ORDER BY name ASC");
$ps->execute();
$patients = $ps->get_result()->fetch_all(MYSQLI_ASSOC);

$preselected_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescribe | MediCare Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg dash-navbar">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-hospital-fill" style="-webkit-text-fill-color:var(--primary-light);"></i>
            <span style="background:linear-gradient(135deg,#fff,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">MediCare Doctor</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dash-main" style="padding:40px 0;"><div class="container">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;">
                <div class="card-body p-4">
                    <h3 class="text-white mb-4"><i class="bi bi-prescription2 text-primary me-2"></i>Write Prescription</h3>

                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-white">Select Patient *</label>
                            <select name="patient_id" class="form-select bg-dark text-white border-secondary" required>
                                <option value="">-- Choose Patient --</option>
                                <?php foreach ($patients as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $preselected_patient == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white">Diagnosis</label>
                            <input type="text" name="diagnosis" class="form-control bg-dark text-white border-secondary" placeholder="e.g. Viral Fever">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white">Medicines * (Comma separated)</label>
                            <textarea name="medicines" class="form-control bg-dark text-white border-secondary" rows="3" required placeholder="e.g. Paracetamol 500mg, Amoxicillin 250mg"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white">Instructions / Dosage</label>
                            <textarea name="instructions" class="form-control bg-dark text-white border-secondary" rows="3" placeholder="e.g. Take 1 tablet after meals twice a day"></textarea>
                        </div>

                        <button type="submit" name="add_prescription" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-plus-circle me-1"></i> Add Prescription
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
