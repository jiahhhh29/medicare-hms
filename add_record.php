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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    $patient_id = intval($_POST['patient_id']);
    $record_title = trim($_POST['record_title']);
    $description = trim($_POST['description']);
    
    if (empty($patient_id) || empty($record_title)) {
        $message = "Patient and Title are required.";
        $message_type = "danger";
    } else {
        $ins = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, record_title, description) VALUES (?, ?, ?, ?)");
        $ins->bind_param("iiss", $patient_id, $doctor_id, $record_title, $description);
        if ($ins->execute()) {
            $message = "Medical record added successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to add record.";
            $message_type = "danger";
        }
    }
}

// Fetch doctor's patients
$ps = $conn->prepare("SELECT DISTINCT p.id, p.name FROM patients p JOIN appointments a ON p.id = a.patient_id WHERE a.doctor_id = ? ORDER BY p.name ASC");
$ps->bind_param("i", $doctor_id);
$ps->execute();
$patients = $ps->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record | MediCare</title>
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
                    <h3 class="text-white mb-4"><i class="bi bi-file-medical text-primary me-2"></i>Add Medical Record</h3>

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
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white">Record Title *</label>
                            <input type="text" name="record_title" class="form-control bg-dark text-white border-secondary" required placeholder="e.g. X-Ray Results">
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white">Description / Details</label>
                            <textarea name="description" class="form-control bg-dark text-white border-secondary" rows="5" placeholder="Detailed notes about the medical record..."></textarea>
                        </div>

                        <button type="submit" name="add_record" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-plus-circle me-1"></i> Save Medical Record
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
