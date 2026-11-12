<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

/* ── Patient ── */
$ps = $conn->prepare("SELECT id, name FROM patients WHERE user_id = ? LIMIT 1");
$ps->bind_param("i", $user_id);
$ps->execute();
$prow = $ps->get_result();
if ($prow->num_rows === 0) die("Patient profile not found.");
$patient    = $prow->fetch_assoc();
$patient_id = $patient['id'];

/* ── Prescriptions — real columns: id, patient_id, doctor_id, diagnosis, medicines, instructions, prescription_date ── */
$stmt = $conn->prepare(
    "SELECT p.*, d.name AS doctor_name, d.specialization
     FROM prescriptions p
     LEFT JOIN doctors d ON p.doctor_id = d.id
     WHERE p.patient_id = ?
     ORDER BY p.prescription_date DESC"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions | MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dash-main { position:relative; z-index:1; padding:40px 0 70px; }
        .rx-card {
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            border-radius:16px; padding:26px 28px; margin-bottom:16px;
            transition:all 0.3s; position:relative; overflow:hidden;
        }
        .rx-card::before {
            content:''; position:absolute; left:0; top:0; bottom:0; width:3px;
            background:linear-gradient(180deg,#2563eb,#06b6d4);
        }
        .rx-card:hover { border-color:rgba(37,99,235,0.35); background:rgba(37,99,235,0.05); transform:translateX(4px); }
        .rx-number {
            position:absolute; top:18px; right:20px;
            font-size:11px; font-weight:700; color:var(--text-dim);
            text-transform:uppercase; letter-spacing:0.5px;
        }
        .med-chip {
            display:inline-flex; align-items:center; gap:5px;
            background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.25);
            color:#34d399; border-radius:8px; padding:5px 12px;
            font-size:13px; font-weight:600; margin:3px;
        }
        .info-row { display:flex; gap:8px; align-items:flex-start; margin-bottom:10px; }
        .info-row i { color:var(--primary-light); font-size:15px; margin-top:2px; flex-shrink:0; }
        .info-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-dim); }
        .info-val { font-size:14px; color:var(--text-muted); line-height:1.6; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dash-navbar">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-hospital-fill" style="-webkit-text-fill-color:var(--primary-light);"></i>
            <span style="background:linear-gradient(135deg,#fff,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">MediCare</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <div class="user-pill d-none d-md-flex">
                <div class="user-avatar"><?= $avatar_letter ?></div>
                <?= htmlspecialchars($user_name) ?>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dash-main"><div class="container">

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb" style="background:transparent;padding:0;font-size:13px;">
            <li class="breadcrumb-item"><a href="dashboard.php" style="color:var(--primary-light);text-decoration:none;"><i class="bi bi-house me-1"></i>Dashboard</a></li>
            <li class="breadcrumb-item active" style="color:var(--text-muted);">My Prescriptions</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-5" style="animation:fadeInUp 0.4s ease both;">
        <div>
            <h2 class="fw-bold mb-1" style="color:#fff;letter-spacing:-0.5px;">
                <i class="bi bi-prescription2 me-2" style="color:#c084fc;"></i>My Prescriptions
            </h2>
            <p style="color:var(--text-muted);font-size:14px;margin:0;">
                <?= count($prescriptions) ?> prescription<?= count($prescriptions)!==1?'s':'' ?> from your doctors
            </p>
        </div>
        <a href="pharmacy.php" class="btn px-4"
           style="background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.35);color:#34d399;">
            <i class="bi bi-capsule-pill me-1"></i>Request Medicine
        </a>
    </div>

    <?php if (empty($prescriptions)): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:70px 20px;">
            <i class="bi bi-file-earmark-medical" style="font-size:56px;color:var(--text-dim);display:block;margin-bottom:16px;"></i>
            <h5 style="color:var(--text-muted);">No Prescriptions Yet</h5>
            <p style="color:var(--text-dim);font-size:14px;">Your doctor's prescriptions will appear here after your consultations.</p>
            <a href="book_appointment.php" class="btn btn-primary mt-2">
                <i class="bi bi-calendar-plus me-2"></i>Book an Appointment
            </a>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($prescriptions as $i => $rx): ?>
    <div class="rx-card" style="animation:fadeInUp <?= 0.3 + $i*0.07 ?>s ease both;">
        <div class="rx-number">Rx #<?= str_pad($rx['id'], 4, '0', STR_PAD_LEFT) ?></div>

        <!-- Header row -->
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,rgba(37,99,235,0.25),rgba(6,182,212,0.15));border:1px solid rgba(37,99,235,0.3);display:flex;align-items:center;justify-content:center;font-size:20px;color:#60a5fa;flex-shrink:0;">
                <i class="bi bi-file-medical-fill"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#fff;">
                    Prescription by
                    <?= htmlspecialchars(preg_match('/^dr\.?\s+/i', $rx['doctor_name'] ?? '') ? $rx['doctor_name'] : ('Dr. ' . ($rx['doctor_name'] ?? 'Unknown'))) ?>
                </div>
                <div style="font-size:12px;color:var(--text-dim);">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= !empty($rx['prescription_date']) ? date('d M Y', strtotime($rx['prescription_date'])) : 'Date not recorded' ?>
                    <?php if (!empty($rx['specialization'])): ?>
                     &bull; <i class="bi bi-briefcase-medical ms-1 me-1"></i><?= htmlspecialchars($rx['specialization']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="row g-3 mt-1">
            <?php if (!empty($rx['diagnosis'])): ?>
            <div class="col-md-6">
                <div class="info-row">
                    <i class="bi bi-activity"></i>
                    <div>
                        <div class="info-label">Diagnosis</div>
                        <div class="info-val"><?= htmlspecialchars($rx['diagnosis']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($rx['instructions'])): ?>
            <div class="col-md-6">
                <div class="info-row">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <div class="info-label">Instructions</div>
                        <div class="info-val"><?= htmlspecialchars($rx['instructions']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($rx['medicines'])): ?>
            <div class="col-12">
                <div class="info-label mb-2"><i class="bi bi-capsule me-1" style="color:#34d399;font-size:13px;"></i>Medicines Prescribed</div>
                <?php
                // split by comma, newline, or semicolon
                $meds = preg_split('/[,;\n]+/', $rx['medicines']);
                foreach ($meds as $med):
                    $med = trim($med);
                    if (empty($med)) continue;
                ?>
                <span class="med-chip"><i class="bi bi-capsule"></i><?= htmlspecialchars($med) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer actions -->
        <div class="d-flex justify-content-end mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06);">
            <a href="pharmacy.php" class="btn btn-sm"
               style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.3);color:#34d399;font-size:12px;">
                <i class="bi bi-cart-plus me-1"></i>Request from Pharmacy
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
