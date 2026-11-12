<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get doctor details
$ds = $conn->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
$ds->bind_param("i", $user_id);
$ds->execute();
$doctor = $ds->get_result()->fetch_assoc();
$doctor_id = $doctor['id'];

// Fetch patients who have booked appointments with this doctor
$ps = $conn->prepare("
    SELECT DISTINCT p.id, p.name, p.gender, p.date_of_birth, p.blood_group, p.phone, u.email
    FROM patients p
    JOIN users u ON p.user_id = u.id
    JOIN appointments a ON p.id = a.patient_id 
    WHERE a.doctor_id = ? 
    ORDER BY p.name ASC
");
$ps->bind_param("i", $doctor_id);
$ps->execute();
$patients = $ps->get_result()->fetch_all(MYSQLI_ASSOC);

// Clean doctor name to avoid duplicate "Dr." title
$clean_name = preg_replace('/^(dr\.?\s*)+/i', '', trim($user_name));
$first_name = explode(' ', $clean_name)[0];
$avatar_letter = strtoupper(substr($clean_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients | MediCare Doctor</title>
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
            <div class="user-pill d-none d-md-flex">
                <div class="user-avatar"><?= $avatar_letter ?></div>
                Dr. <?= htmlspecialchars($first_name) ?>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dash-main" style="padding:40px 0;"><div class="container">
    <h2 class="text-white mb-4"><i class="bi bi-people text-success me-2"></i>My Patients</h2>

    <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;">
        <div class="card-body p-4">
            <?php if (empty($patients)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">You don't have any patients assigned yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Gender / Age</th>
                                <th>Blood Group</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $p): 
                                $age = "N/A";
                                if (!empty($p['date_of_birth'])) {
                                    $dob = new DateTime($p['date_of_birth']);
                                    $now = new DateTime();
                                    $age = $now->diff($dob)->y . " yrs";
                                }
                            ?>
                            <tr>
                                <td>
                                    <h6 class="mb-0 text-white"><?= htmlspecialchars($p['name']) ?></h6>
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['gender'] ?? 'N/A') ?> <br>
                                    <small class="text-muted"><?= $age ?></small>
                                </td>
                                <td>
                                    <span class="badge" style="background:rgba(239,68,68,0.2);color:#f87171;border:1px solid rgba(239,68,68,0.3);">
                                        <?= htmlspecialchars($p['blood_group'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td>
                                    <small><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($p['email'] ?? '-') ?></small><br>
                                    <small><i class="bi bi-phone me-1"></i><?= htmlspecialchars($p['phone'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <a href="add_record.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-info text-white me-1" title="Add Medical Record">
                                        <i class="bi bi-file-earmark-medical"></i>
                                    </a>
                                    <a href="prescribe.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary" title="Write Prescription">
                                        <i class="bi bi-prescription2"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
