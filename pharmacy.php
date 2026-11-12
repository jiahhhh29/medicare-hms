<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message      = "";
$message_type = "";

/* ── Get patient ── */
$ps = $conn->prepare("SELECT id, name FROM patients WHERE user_id = ? LIMIT 1");
$ps->bind_param("i", $user_id);
$ps->execute();
$pres = $ps->get_result();

if ($pres->num_rows === 0) {
    die("Patient profile not found.");
}
$patient    = $pres->fetch_assoc();
$patient_id = $patient['id'];

/* ── Ensure pharmacy_requests table exists ── */
$conn->query("CREATE TABLE IF NOT EXISTS pharmacy_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    quantity     INT NOT NULL DEFAULT 1,
    notes        TEXT,
    status       ENUM('Pending','Dispensed','Cancelled') NOT NULL DEFAULT 'Pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dispensed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
)");

/* ── Handle new request ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $medicine = trim($_POST['medicine_name']);
    $quantity = intval($_POST['quantity']);
    $notes    = trim($_POST['notes']);

    if (empty($medicine) || $quantity < 1) {
        $message      = "Please enter a medicine name and valid quantity.";
        $message_type = "danger";
    } else {
        $ins = $conn->prepare(
            "INSERT INTO pharmacy_requests (patient_id, medicine_name, quantity, notes)
             VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("isis", $patient_id, $medicine, $quantity, $notes);
        if ($ins->execute()) {
            $message      = "Medicine request submitted successfully! The pharmacy will dispense it shortly.";
            $message_type = "success";
        } else {
            $message      = "Failed to submit request. Please try again.";
            $message_type = "danger";
        }
    }
}

/* ── Get patient's prescriptions ── */
$prescriptions = [];
$psc = $conn->prepare(
    "SELECT p.id, p.medicines, p.instructions, p.prescription_date,
            d.name AS doctor_name
     FROM prescriptions p
     LEFT JOIN doctors d ON p.doctor_id = d.id
     WHERE p.patient_id = ?
     ORDER BY p.prescription_date DESC"
);
if ($psc) {
    $psc->bind_param("i", $patient_id);
    $psc->execute();
    $prescriptions = $psc->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ── Get pharmacy requests ── */
$requests = $conn->prepare(
    "SELECT * FROM pharmacy_requests WHERE patient_id = ? ORDER BY requested_at DESC"
);
$requests->bind_param("i", $patient_id);
$requests->execute();
$my_requests = $requests->get_result()->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy | MediCare Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dash-main { position: relative; z-index: 1; padding: 40px 0 70px; }

        .pharma-header {
            background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(6,182,212,0.08));
            border: 1px solid rgba(16,185,129,0.2);
            border-radius: 20px;
            padding: 32px 36px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .pharma-logo {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #059669, #06b6d4);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: #fff;
            box-shadow: 0 10px 28px rgba(5,150,105,0.35);
            flex-shrink: 0;
        }

        .tab-nav {
            display: flex;
            gap: 4px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 28px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 16px;
            border-radius: 9px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s;
            font-family: 'Inter', sans-serif;
        }

        .tab-btn.active {
            background: rgba(37,99,235,0.2);
            color: #60a5fa;
            border: 1px solid rgba(37,99,235,0.3);
        }

        .tab-btn:hover:not(.active) {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .rx-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 14px;
            transition: all 0.3s;
            position: relative;
        }

        .rx-card:hover {
            border-color: rgba(16,185,129,0.35);
            background: rgba(16,185,129,0.05);
            transform: translateX(4px);
        }

        .rx-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #34d399;
            flex-shrink: 0;
        }

        .req-form-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px;
            padding: 32px;
            margin-bottom: 28px;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 0;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: var(--radius-sm);
            overflow: hidden;
            width: fit-content;
        }

        .qty-btn {
            width: 40px; height: 44px;
            background: rgba(255,255,255,0.06);
            border: none;
            color: var(--text-primary);
            font-size: 18px;
            cursor: pointer;
            transition: background 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .qty-btn:hover { background: rgba(37,99,235,0.2); color: #60a5fa; }

        .qty-display {
            width: 60px; height: 44px;
            text-align: center;
            background: rgba(255,255,255,0.04);
            border: none;
            border-left: 1px solid rgba(255,255,255,0.08);
            border-right: 1px solid rgba(255,255,255,0.08);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-dim);
        }
        .empty-state i { font-size: 56px; display: block; margin-bottom: 16px; }
        .empty-state p { font-size: 14px; }
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
            <div class="user-pill d-none d-md-flex">
                <div class="user-avatar"><?php echo $avatar_letter; ?></div>
                <?php echo htmlspecialchars($user_name); ?>
            </div>
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
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb" style="background:transparent; padding:0; font-size:13px;">
            <li class="breadcrumb-item">
                <a href="dashboard.php" style="color:var(--primary-light); text-decoration:none;">
                    <i class="bi bi-house me-1"></i>Dashboard
                </a>
            </li>
            <li class="breadcrumb-item active" style="color:var(--text-muted);">Pharmacy</li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="pharma-header" style="animation: fadeInUp 0.5s ease both;">
        <div class="pharma-logo"><i class="bi bi-capsule-pill"></i></div>
        <div>
            <h3 class="fw-bold mb-1" style="color:#fff; letter-spacing:-0.5px;">MediCare Pharmacy</h3>
            <p style="color:var(--text-muted); font-size:14px; margin:0;">
                View your prescriptions and request medicines. Fast, simple, secure.
            </p>
        </div>
        <div class="ms-auto d-none d-lg-flex align-items-center gap-3" style="flex-shrink:0;">
            <div class="text-center">
                <div style="font-size:22px; font-weight:800; color:#34d399;"><?php echo count($prescriptions); ?></div>
                <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Prescriptions</div>
            </div>
            <div style="width:1px; height:40px; background:rgba(255,255,255,0.1);"></div>
            <div class="text-center">
                <div style="font-size:22px; font-weight:800; color:#22d3ee;"><?php echo count($my_requests); ?></div>
                <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Requests</div>
            </div>
        </div>
    </div>

    <!-- Alert -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" style="animation: fadeInUp 0.4s ease both;">
        <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tab-nav" style="animation: fadeInUp 0.5s 0.1s ease both;">
        <button class="tab-btn active" onclick="switchTab('prescriptions', this)">
            <i class="bi bi-prescription2 me-2"></i>My Prescriptions
        </button>
        <button class="tab-btn" onclick="switchTab('request', this)">
            <i class="bi bi-plus-circle me-2"></i>Request Medicine
        </button>
        <button class="tab-btn" onclick="switchTab('my-requests', this)">
            <i class="bi bi-clock-history me-2"></i>My Requests
            <?php if (count($my_requests) > 0): ?>
            <span class="badge ms-1" style="background:rgba(37,99,235,0.3);color:#93c5fd;">
                <?php echo count($my_requests); ?>
            </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ════ TAB: PRESCRIPTIONS ════ -->
    <div id="tab-prescriptions" class="tab-panel active" style="animation: fadeInUp 0.5s 0.2s ease both;">
        <?php if (empty($prescriptions)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-file-earmark-medical" style="color:var(--text-dim);"></i>
                    <h5 style="color:var(--text-muted);">No Prescriptions Yet</h5>
                    <p>Your doctor's prescriptions will appear here after your appointments.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($prescriptions as $rx): ?>
            <div class="col-lg-6">
                <div class="rx-card">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rx-icon"><i class="bi bi-capsule"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1" style="color:#fff;">
                                    <?php echo htmlspecialchars($rx['medicines'] ?? '—'); ?>
                                </h6>
                                <small style="color:var(--text-dim); font-size:11px;">
                                    <?php echo !empty($rx['prescription_date']) ? date('d M Y', strtotime($rx['prescription_date'])) : '—'; ?>
                                </small>
                            </div>
                            <?php if (!empty($rx['instructions'])): ?>
                            <div class="mb-2">
                                <span style="font-size:12px; color:var(--text-muted);">
                                    <i class="bi bi-info-circle me-1" style="color:#60a5fa;"></i>
                                    <?php echo htmlspecialchars($rx['instructions']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($rx['doctor_name'])): ?>
                            <div>
                                <span class="badge" style="background:rgba(37,99,235,0.15);color:#93c5fd;border:1px solid rgba(37,99,235,0.25);font-size:11px;">
                                    <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars(preg_match('/^dr\.?\s+/i', $rx['doctor_name']) ? $rx['doctor_name'] : ('Dr. ' . $rx['doctor_name'])) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Quick request button -->
                    <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06);">
                        <button class="btn btn-sm"
                                style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.3);color:#34d399;font-size:12px;"
                                onclick="prefillRequest('<?php echo htmlspecialchars(addslashes($rx['medicines'] ?? ''), ENT_QUOTES); ?>')">
                            <i class="bi bi-cart-plus me-1"></i>Request from Pharmacy
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ════ TAB: REQUEST MEDICINE ════ -->
    <div id="tab-request" class="tab-panel">
        <div class="req-form-card" style="animation: fadeInUp 0.4s ease both;">
            <h5 class="fw-bold mb-1" style="color:#fff; letter-spacing:-0.3px;">
                <i class="bi bi-cart-plus text-success me-2"></i>Request a Medicine
            </h5>
            <p style="color:var(--text-muted); font-size:13px; margin-bottom:28px;">
                Submit a medicine request and the pharmacy team will dispense it.
            </p>

            <form method="POST" action="">

                <div class="mb-4">
                    <label class="form-label">Medicine Name <span style="color:#f87171;">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-right:none;color:#34d399;">
                            <i class="bi bi-capsule"></i>
                        </span>
                        <input type="text" name="medicine_name" id="medicineInput" class="form-control"
                               placeholder="e.g. Paracetamol 500mg" required
                               value="<?php echo htmlspecialchars($_POST['medicine_name'] ?? ''); ?>"
                               style="border-left:none;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Quantity <span style="color:#f87171;">*</span></label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                        <input type="number" name="quantity" id="qtyInput"
                               class="qty-display" value="1" min="1" max="99" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    <small style="color:var(--text-dim); font-size:12px; margin-top:6px; display:block;">
                        Number of units / strips
                    </small>
                </div>

                <div class="mb-5">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="e.g. This is prescribed by Dr. Smith — refer prescription #12..."><?php
                        echo htmlspecialchars($_POST['notes'] ?? '');
                    ?></textarea>
                </div>

                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            onclick="switchTab('prescriptions', document.querySelector('.tab-btn'))">
                        <i class="bi bi-arrow-left me-1"></i>View Prescriptions
                    </button>
                    <button type="submit" name="submit_request" class="btn btn-primary flex-fill py-3" style="font-size:15px;">
                        <i class="bi bi-send me-2"></i>Submit Request
                    </button>
                </div>

            </form>
        </div>

        <!-- Info box -->
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:#fff;">
                    <i class="bi bi-info-circle text-primary me-2"></i>How It Works
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(37,99,235,0.2);border:1px solid rgba(37,99,235,0.3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#60a5fa;flex-shrink:0;">1</div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#fff;">Submit Request</div>
                                <div style="font-size:12px;color:var(--text-muted);">Enter the medicine and quantity needed.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(16,185,129,0.2);border:1px solid rgba(16,185,129,0.3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#34d399;flex-shrink:0;">2</div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#fff;">Pharmacy Reviews</div>
                                <div style="font-size:12px;color:var(--text-muted);">Pharmacist verifies against your prescription.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(6,182,212,0.2);border:1px solid rgba(6,182,212,0.3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#22d3ee;flex-shrink:0;">3</div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#fff;">Medicine Dispensed</div>
                                <div style="font-size:12px;color:var(--text-muted);">Collect your medicine from the pharmacy counter.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ TAB: MY REQUESTS ════ -->
    <div id="tab-my-requests" class="tab-panel">
        <?php if (empty($my_requests)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-clock-history" style="color:var(--text-dim);"></i>
                    <h5 style="color:var(--text-muted);">No Requests Yet</h5>
                    <p>Your pharmacy medicine requests will appear here.</p>
                    <button class="btn btn-primary mt-2" onclick="switchTab('request', document.querySelectorAll('.tab-btn')[1])">
                        <i class="bi bi-plus-circle me-2"></i>Make a Request
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-dark-custom w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Medicine</th>
                                <th>Qty</th>
                                <th>Notes</th>
                                <th>Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($my_requests as $i => $req): ?>
                            <tr>
                                <td style="color:var(--text-dim);"><?php echo $i + 1; ?></td>
                                <td>
                                    <i class="bi bi-capsule me-2" style="color:#34d399;"></i>
                                    <strong style="color:var(--text-primary);">
                                        <?php echo htmlspecialchars($req['medicine_name']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge" style="background:rgba(37,99,235,0.2);color:#93c5fd;border:1px solid rgba(37,99,235,0.3);">
                                        <?php echo $req['quantity']; ?> unit<?php echo $req['quantity'] > 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td style="max-width:200px; font-size:13px;">
                                    <?php echo htmlspecialchars($req['notes'] ?: '—'); ?>
                                </td>
                                <td style="font-size:12px; white-space:nowrap;">
                                    <?php echo date('d M Y, g:i A', strtotime($req['requested_at'])); ?>
                                </td>
                                <td>
                                    <?php
                                    $st = strtolower($req['status']);
                                    $pill = match($st) {
                                        'dispensed'  => 'dispensed',
                                        'cancelled'  => 'cancelled',
                                        default      => 'pending'
                                    };
                                    ?>
                                    <span class="status-pill <?php echo $pill; ?>">
                                        <i class="bi bi-circle-fill" style="font-size:6px;"></i>
                                        <?php echo htmlspecialchars($req['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Tab switching ── */
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    if (btn) btn.classList.add('active');
}

/* ── Quantity control ── */
function changeQty(delta) {
    const inp = document.getElementById('qtyInput');
    let val = parseInt(inp.value) + delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    inp.value = val;
}

/* ── Prefill from prescription ── */
function prefillRequest(medicineName) {
    document.getElementById('medicineInput').value = medicineName;
    switchTab('request', document.querySelectorAll('.tab-btn')[1]);
    document.getElementById('medicineInput').focus();
}

/* ── Auto-switch to Request tab after success ── */
<?php if ($message_type === 'success'): ?>
switchTab('my-requests', document.querySelectorAll('.tab-btn')[2]);
<?php endif; ?>
</script>
</body>
</html>
