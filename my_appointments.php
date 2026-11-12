<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = ""; $message_type = "";

/* ── Get patient ── */
$ps = $conn->prepare("SELECT id, name FROM patients WHERE user_id = ? LIMIT 1");
$ps->bind_param("i", $user_id);
$ps->execute();
$prow = $ps->get_result();
if ($prow->num_rows === 0) { die("Patient profile not found."); }
$patient    = $prow->fetch_assoc();
$patient_id = $patient['id'];

/* ── Handle cancel ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = intval($_POST['cancel_id']);
    $upd = $conn->prepare(
        "UPDATE appointments SET status='Cancelled'
         WHERE id = ? AND patient_id = ? AND status = 'Pending'"
    );
    $upd->bind_param("ii", $cancel_id, $patient_id);
    if ($upd->execute() && $upd->affected_rows > 0) {
        $message = "Appointment cancelled successfully.";
        $message_type = "success";
    } else {
        $message = "Cannot cancel this appointment (already approved/cancelled).";
        $message_type = "warning";
    }
}

/* ── Filter ── */
$filter = $_GET['filter'] ?? 'all';
$allowed = ['all','Pending','Approved','Cancelled','Completed'];
if (!in_array($filter, $allowed)) $filter = 'all';

$where = $filter === 'all' ? "" : " AND a.status = '$filter' ";

/* ── Fetch appointments ── */
$sql = "SELECT a.*, d.name AS doctor_name, d.specialization
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ? $where
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Count by status ── */
$counts = ['all'=>0,'Pending'=>0,'Approved'=>0,'Cancelled'=>0,'Completed'=>0];
$cq = $conn->prepare("SELECT status, COUNT(*) as c FROM appointments WHERE patient_id = ? GROUP BY status");
$cq->bind_param("i", $patient_id);
$cq->execute();
$cres = $cq->get_result();
while ($r = $cres->fetch_assoc()) {
    $counts[$r['status']] = $r['c'];
    $counts['all'] += $r['c'];
}

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dash-main { position:relative; z-index:1; padding:40px 0 70px; }
        .filter-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:24px; }
        .filter-tab {
            padding:8px 18px; border-radius:100px; font-size:13px; font-weight:600;
            border:1px solid rgba(255,255,255,0.10); color:var(--text-muted);
            background:rgba(255,255,255,0.04); text-decoration:none; transition:all 0.25s;
        }
        .filter-tab:hover { color:#fff; border-color:rgba(255,255,255,0.25); background:rgba(255,255,255,0.08); }
        .filter-tab.active { background:rgba(37,99,235,0.2); border-color:rgba(37,99,235,0.5); color:#60a5fa; }
        .filter-tab .count { font-size:11px; opacity:0.7; margin-left:5px; }
        .appt-row {
            background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07);
            border-radius:14px; padding:20px 22px; margin-bottom:12px;
            transition:all 0.25s;
        }
        .appt-row:hover { border-color:rgba(37,99,235,0.3); background:rgba(37,99,235,0.05); }
        .doctor-avatar {
            width:44px; height:44px; border-radius:12px;
            background:linear-gradient(135deg,rgba(37,99,235,0.3),rgba(6,182,212,0.2));
            border:1px solid rgba(37,99,235,0.3);
            display:flex; align-items:center; justify-content:center;
            font-size:18px; color:#60a5fa; flex-shrink:0;
        }
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
            <li class="breadcrumb-item active" style="color:var(--text-muted);">My Appointments</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="animation:fadeInUp 0.4s ease both;">
        <div>
            <h2 class="fw-bold mb-1" style="color:#fff;letter-spacing:-0.5px;">
                <i class="bi bi-calendar2-week text-primary me-2"></i>My Appointments
            </h2>
            <p style="color:var(--text-muted);font-size:14px;margin:0;">Track and manage all your hospital appointments</p>
        </div>
        <a href="book_appointment.php" class="btn btn-primary px-4">
            <i class="bi bi-plus-lg me-1"></i>Book New
        </a>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <i class="bi bi-<?= $message_type==='success'?'check-circle-fill':'exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stat Row -->
    <div class="row g-3 mb-4" style="animation:fadeInUp 0.4s 0.1s ease both;">
        <div class="col-6 col-md-3">
            <div class="stat-card blue" style="padding:20px;">
                <div class="stat-value" style="font-size:28px;"><?= $counts['all'] ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="padding:20px;background:rgba(245,158,11,0.08);border-color:rgba(245,158,11,0.2);">
                <div class="stat-value" style="font-size:28px;color:#fbbf24;"><?= $counts['Pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card green" style="padding:20px;">
                <div class="stat-value" style="font-size:28px;"><?= $counts['Approved'] ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="padding:20px;background:rgba(6,182,212,0.08);border-color:rgba(6,182,212,0.2);">
                <div class="stat-value" style="font-size:28px;color:#22d3ee;"><?= $counts['Completed'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs" style="animation:fadeInUp 0.4s 0.2s ease both;">
        <?php
        $filters = ['all'=>'All','Pending'=>'Pending','Approved'=>'Approved','Completed'=>'Completed','Cancelled'=>'Cancelled'];
        foreach ($filters as $val=>$label):
            $active = ($filter === $val) ? 'active' : '';
        ?>
        <a href="?filter=<?= $val ?>" class="filter-tab <?= $active ?>">
            <?= $label ?> <span class="count"><?= $counts[$val] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Appointments List -->
    <div style="animation:fadeInUp 0.4s 0.3s ease both;">
        <?php if (empty($appointments)): ?>
        <div class="card">
            <div class="card-body" style="text-align:center;padding:70px 20px;">
                <i class="bi bi-calendar-x" style="font-size:56px;color:var(--text-dim);display:block;margin-bottom:16px;"></i>
                <h5 style="color:var(--text-muted);">No appointments found</h5>
                <p style="color:var(--text-dim);font-size:14px;">
                    <?= $filter==='all' ? "You haven't booked any appointments yet." : "No $filter appointments found." ?>
                </p>
                <a href="book_appointment.php" class="btn btn-primary mt-2">
                    <i class="bi bi-calendar-plus me-2"></i>Book an Appointment
                </a>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($appointments as $appt):
            $st = strtolower($appt['status'] ?? 'pending');
            $pillClass = match($st) {
                'approved'  => 'approved',
                'cancelled' => 'cancelled',
                'completed' => 'completed',
                default     => 'pending'
            };
            $isPast = $appt['appointment_date'] < date('Y-m-d');
        ?>
        <div class="appt-row">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="doctor-avatar"><i class="bi bi-person-badge"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-1" style="color:#fff;">
                                <?= htmlspecialchars(preg_match('/^dr\.?\s+/i', $appt['doctor_name'] ?? '') ? $appt['doctor_name'] : ('Dr. ' . ($appt['doctor_name'] ?? 'Unassigned'))) ?>
                            </h6>
                            <?php if (!empty($appt['specialization'])): ?>
                            <span style="font-size:12px;color:var(--text-dim);">
                                <i class="bi bi-briefcase-medical me-1"></i><?= htmlspecialchars($appt['specialization']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="status-pill <?= $pillClass ?>">
                            <i class="bi bi-circle-fill" style="font-size:6px;"></i>
                            <?= htmlspecialchars($appt['status']) ?>
                        </span>
                    </div>
                    <div class="d-flex gap-4 mt-2 flex-wrap">
                        <span style="font-size:13px;color:var(--text-muted);">
                            <i class="bi bi-calendar3 me-1 text-primary"></i>
                            <?= date('d M Y', strtotime($appt['appointment_date'])) ?>
                        </span>
                        <span style="font-size:13px;color:var(--text-muted);">
                            <i class="bi bi-clock me-1" style="color:#fbbf24;"></i>
                            <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                        </span>
                        <?php if (!empty($appt['reason'])): ?>
                        <span style="font-size:13px;color:var(--text-muted);">
                            <i class="bi bi-chat-text me-1" style="color:#34d399;"></i>
                            <?= htmlspecialchars(substr($appt['reason'], 0, 40)) . (strlen($appt['reason'])>40?'...':'') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Cancel button: only for Pending future appointments -->
                <?php if ($appt['status'] === 'Pending' && !$isPast): ?>
                <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                    <input type="hidden" name="cancel_id" value="<?= $appt['id'] ?>">
                    <button type="submit" class="btn btn-sm"
                            style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;font-size:12px;">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
