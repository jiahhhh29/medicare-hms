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

/* ── Medical Records — columns: id, patient_id, doctor_id, record_title, description, record_date ── */
$stmt = $conn->prepare(
    "SELECT mr.*, d.name AS doctor_name
     FROM medical_records mr
     LEFT JOIN doctors d ON mr.doctor_id = d.id
     WHERE mr.patient_id = ?
     ORDER BY mr.record_date DESC"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Lab Reports — columns: id, patient_id, test_name, result, report_date, status ── */
$ls = $conn->prepare(
    "SELECT * FROM lab_reports WHERE patient_id = ? ORDER BY report_date DESC"
);
$ls->bind_param("i", $patient_id);
$ls->execute();
$lab_reports = $ls->get_result()->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records | MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dash-main { position:relative; z-index:1; padding:40px 0 70px; }
        .tab-nav { display:flex; gap:4px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:5px; margin-bottom:24px; }
        .tab-btn { flex:1; padding:10px 16px; border-radius:9px; border:none; background:transparent; color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer; transition:all 0.25s; font-family:'Inter',sans-serif; }
        .tab-btn.active { background:rgba(37,99,235,0.2); color:#60a5fa; border:1px solid rgba(37,99,235,0.3); }
        .tab-btn:hover:not(.active) { background:rgba(255,255,255,0.06); color:#fff; }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }
        .rec-card {
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            border-radius:16px; padding:24px 26px; margin-bottom:14px;
            transition:all 0.25s; position:relative; overflow:hidden;
        }
        .rec-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:linear-gradient(180deg,#059669,#06b6d4); }
        .rec-card:hover { border-color:rgba(16,185,129,0.35); background:rgba(16,185,129,0.04); transform:translateX(4px); }
        .lab-card {
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            border-radius:16px; padding:24px 26px; margin-bottom:14px;
            transition:all 0.25s; position:relative; overflow:hidden;
        }
        .lab-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:linear-gradient(180deg,#7c3aed,#ec4899); }
        .lab-card:hover { border-color:rgba(124,58,237,0.35); background:rgba(124,58,237,0.04); transform:translateX(4px); }
        .result-box { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:12px 16px; margin-top:10px; font-size:14px; color:var(--text-muted); line-height:1.7; }
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
            <li class="breadcrumb-item active" style="color:var(--text-muted);">Medical Records</li>
        </ol>
    </nav>

    <div class="mb-5" style="animation:fadeInUp 0.4s ease both;">
        <h2 class="fw-bold mb-1" style="color:#fff;letter-spacing:-0.5px;">
            <i class="bi bi-file-medical-fill me-2 text-success"></i>Health Records
        </h2>
        <p style="color:var(--text-muted);font-size:14px;margin:0;">
            Your complete medical history — records &amp; lab reports
        </p>
    </div>

    <!-- Summary stats -->
    <div class="row g-3 mb-4" style="animation:fadeInUp 0.4s 0.1s ease both;">
        <div class="col-6">
            <div class="stat-card green" style="padding:20px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size:28px;"><?= count($records) ?></div>
                        <div class="stat-label">Medical Records</div>
                    </div>
                    <div class="stat-icon green"><i class="bi bi-file-medical"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-card" style="padding:20px;background:rgba(124,58,237,0.08);border-color:rgba(124,58,237,0.2);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value" style="font-size:28px;color:#c084fc;"><?= count($lab_reports) ?></div>
                        <div class="stat-label">Lab Reports</div>
                    </div>
                    <div class="stat-icon" style="background:rgba(124,58,237,0.2);color:#c084fc;"><i class="bi bi-eyedropper"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tab-nav" style="animation:fadeInUp 0.4s 0.15s ease both;">
        <button class="tab-btn active" onclick="switchTab('records',this)">
            <i class="bi bi-file-medical me-2"></i>Medical Records <span style="opacity:.6;font-size:11px;">(<?= count($records) ?>)</span>
        </button>
        <button class="tab-btn" onclick="switchTab('lab',this)">
            <i class="bi bi-eyedropper me-2"></i>Lab Reports <span style="opacity:.6;font-size:11px;">(<?= count($lab_reports) ?>)</span>
        </button>
    </div>

    <!-- ── Medical Records ── -->
    <div id="tab-records" class="tab-panel active" style="animation:fadeInUp 0.4s 0.2s ease both;">
        <?php if (empty($records)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:70px 20px;">
            <i class="bi bi-folder2-open" style="font-size:56px;color:var(--text-dim);display:block;margin-bottom:16px;"></i>
            <h5 style="color:var(--text-muted);">No Medical Records</h5>
            <p style="color:var(--text-dim);font-size:14px;">Your doctor will add records after your consultation.</p>
        </div></div>
        <?php else: ?>
        <?php foreach ($records as $i => $rec): ?>
        <div class="rec-card" style="animation:fadeInUp <?= 0.3+$i*0.07 ?>s ease both;">
            <div class="d-flex align-items-start gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.25);display:flex;align-items:center;justify-content:center;font-size:20px;color:#34d399;flex-shrink:0;">
                    <i class="bi bi-file-medical-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="fw-bold mb-1" style="color:#fff;">
                            <?= htmlspecialchars($rec['record_title'] ?? 'Medical Record') ?>
                        </h6>
                        <span style="font-size:11px;color:var(--text-dim);white-space:nowrap;">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= !empty($rec['record_date']) ? date('d M Y', strtotime($rec['record_date'])) : '—' ?>
                        </span>
                    </div>
                    <?php if (!empty($rec['doctor_name'])): ?>
                    <span class="badge mb-2" style="background:rgba(37,99,235,0.15);color:#93c5fd;border:1px solid rgba(37,99,235,0.25);font-size:11px;">
                        <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars(preg_match('/^dr\.?\s+/i', $rec['doctor_name']) ? $rec['doctor_name'] : ('Dr. ' . $rec['doctor_name'])) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($rec['description'])): ?>
                    <div class="result-box"><?= nl2br(htmlspecialchars($rec['description'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Lab Reports ── -->
    <div id="tab-lab" class="tab-panel" style="animation:fadeInUp 0.4s 0.2s ease both;">
        <?php if (empty($lab_reports)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:70px 20px;">
            <i class="bi bi-eyedropper" style="font-size:56px;color:var(--text-dim);display:block;margin-bottom:16px;"></i>
            <h5 style="color:var(--text-muted);">No Lab Reports</h5>
            <p style="color:var(--text-dim);font-size:14px;">Lab test results will appear here once processed.</p>
        </div></div>
        <?php else: ?>
        <?php foreach ($lab_reports as $i => $lab): ?>
        <div class="lab-card" style="animation:fadeInUp <?= 0.3+$i*0.07 ?>s ease both;">
            <div class="d-flex align-items-start gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(124,58,237,0.15);border:1px solid rgba(124,58,237,0.25);display:flex;align-items:center;justify-content:center;font-size:20px;color:#c084fc;flex-shrink:0;">
                    <i class="bi bi-eyedropper"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <h6 class="fw-bold mb-1" style="color:#fff;">
                            <?= htmlspecialchars($lab['test_name'] ?? 'Lab Test') ?>
                        </h6>
                        <div class="d-flex gap-2 align-items-center">
                            <?php
                            $labStatus = strtolower($lab['status'] ?? 'available');
                            $lc = match($labStatus) {
                                'available' => ['rgba(16,185,129,0.15)','rgba(16,185,129,0.3)','#34d399'],
                                'pending'   => ['rgba(245,158,11,0.15)','rgba(245,158,11,0.3)','#fbbf24'],
                                default     => ['rgba(100,116,139,0.15)','rgba(100,116,139,0.3)','#94a3b8']
                            };
                            ?>
                            <span class="badge" style="background:<?=$lc[0]?>;border:1px solid <?=$lc[1]?>;color:<?=$lc[2]?>;font-size:11px;">
                                <?= htmlspecialchars(ucfirst($lab['status'] ?? 'Available')) ?>
                            </span>
                            <span style="font-size:11px;color:var(--text-dim);">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= !empty($lab['report_date']) ? date('d M Y', strtotime($lab['report_date'])) : '—' ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($lab['result'])): ?>
                    <div class="result-box">
                        <i class="bi bi-clipboard2-pulse me-2" style="color:#c084fc;"></i>
                        <strong style="color:var(--text-primary);">Result:</strong>
                        <?= nl2br(htmlspecialchars($lab['result'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    if (btn) btn.classList.add('active');
}
</script>
</body></html>
