<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message   = "";
$message_type = "";

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $icon = trim($_POST['icon'] ?? 'bi-hospital-fill');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($code)) {
        $message = "Department Name and Code are required.";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO departments (name, code, icon, description, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->bind_param("ssss", $name, $code, $icon, $description);
        if ($stmt->execute()) {
            $message = "Department '$name' added successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Fetch Departments with doctor counts and head doctor
$departments = $conn->query("
    SELECT dep.*, 
           COUNT(d.id) AS doctor_count,
           hd.name AS head_doctor_name,
           hd.doctor_code AS head_doctor_code
    FROM departments dep
    LEFT JOIN doctors d ON dep.id = d.department_id
    LEFT JOIN doctors hd ON dep.head_doctor_id = hd.id
    GROUP BY dep.id
    ORDER BY dep.name ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch all doctors with department details
$doctors = $conn->query("
    SELECT d.id, d.doctor_code, d.name, d.specialization, d.experience, d.phone, d.room_number,
           u.email, dep.name AS department_name, dep.code AS department_code
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN departments dep ON d.department_id = dep.id
    ORDER BY dep.name ASC, d.name ASC
")->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments & Doctors | MediCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dept-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px;
            padding: 24px;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dept-card:hover {
            border-color: rgba(37,99,235,0.4);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.3);
        }
        .dept-icon-box {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(37,99,235,0.2), rgba(6,182,212,0.2));
            border: 1px solid rgba(37,99,235,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #38bdf8;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dash-navbar">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-shield-lock-fill" style="-webkit-text-fill-color:var(--primary-light);"></i>
            <span style="background:linear-gradient(135deg,#fff,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">MediCare Admin</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
            <a href="manage_users.php" class="btn btn-outline-light btn-sm"><i class="bi bi-people me-1"></i>Users</a>
            <a href="../doctor-login.php" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Doctor Login Portal</a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dash-main" style="padding:40px 0;"><div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-white mb-1 fw-bold">
                <i class="bi bi-building-fill-gear text-info me-2"></i>Hospital Departments & Doctors
            </h2>
            <p style="color:var(--text-muted); margin:0;">
                Overview of all specialized departments, active staff, doctor IDs, and credentials.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#doctorCredentialsModal">
                <i class="bi bi-key-fill text-warning me-1"></i>All Doctor Credentials
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                <i class="bi bi-plus-circle me-1"></i>Add Department
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Departments Grid -->
    <div class="row g-4 mb-5">
        <?php foreach ($departments as $dept): ?>
        <div class="col-md-6 col-lg-4">
            <div class="dept-card">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="dept-icon-box">
                            <i class="bi <?= htmlspecialchars($dept['icon'] ?? 'bi-hospital') ?>"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-info border border-info-subtle px-2 py-1 font-monospace" style="font-size:12px;">
                            <?= htmlspecialchars($dept['code']) ?>
                        </span>
                    </div>

                    <h4 class="fw-bold text-white mb-2"><?= htmlspecialchars($dept['name']) ?></h4>
                    <p style="color:var(--text-muted); font-size:13px; line-height:1.5;">
                        <?= htmlspecialchars($dept['description'] ?? 'Comprehensive care and treatments.') ?>
                    </p>
                </div>

                <div class="pt-3 border-top border-secondary border-opacity-25 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted" style="font-size:12px;">Active Doctors:</small>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <?= $dept['doctor_count'] ?> Doctor(s)
                        </span>
                    </div>
                    <?php if (!empty($dept['head_doctor_name'])): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted" style="font-size:12px;">Head of Dept:</small>
                        <small class="text-white fw-bold" style="font-size:12px;">
                            <?= htmlspecialchars($dept['head_doctor_name']) ?>
                            (<?= htmlspecialchars($dept['head_doctor_code'] ?? '') ?>)
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Doctors Directory Table -->
    <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:18px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h4 class="text-white mb-0">
                    <i class="bi bi-people-fill text-primary me-2"></i>Doctors Directory & Login IDs
                </h4>
                <span class="badge bg-info px-3 py-2 rounded-pill"><?= count($doctors) ?> Total Doctors</span>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Doctor ID</th>
                            <th>Doctor Name</th>
                            <th>Department</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Email (Login ID)</th>
                            <th>Default Password</th>
                            <th>Portal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doc): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary font-monospace" style="font-size:12px;">
                                    <?= htmlspecialchars($doc['doctor_code'] ?? 'DOC-'.$doc['id']) ?>
                                </span>
                            </td>
                            <td>
                                <strong class="text-white"><?= htmlspecialchars($doc['name']) ?></strong>
                                <?php if (!empty($doc['room_number'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($doc['room_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-info border">
                                    <?= htmlspecialchars($doc['department_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($doc['specialization']) ?></td>
                            <td><?= $doc['experience'] ?> Years</td>
                            <td><code><?= htmlspecialchars($doc['email']) ?></code></td>
                            <td><span class="badge bg-dark border border-secondary text-warning">password123</span></td>
                            <td>
                                <a href="../doctor-login.php" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:12px;">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div></div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <form method="POST">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title"><i class="bi bi-plus-circle text-primary me-2"></i>Add Hospital Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="name" class="form-control bg-dark text-white border-secondary" placeholder="e.g. Nephrology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Code</label>
                        <input type="text" name="code" class="form-control bg-dark text-white border-secondary" placeholder="e.g. NEPH" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bootstrap Icon Class</label>
                        <input type="text" name="icon" class="form-control bg-dark text-white border-secondary" value="bi-hospital-fill">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Overview of services provided..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_department" class="btn btn-primary">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Doctor Credentials Quick Reference Modal -->
<div class="modal fade" id="doctorCredentialsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-key-fill text-warning me-2"></i>All Demo Doctor Credentials</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 450px;">
                    <table class="table table-dark table-striped align-middle mb-0">
                        <thead class="sticky-top bg-dark">
                            <tr>
                                <th>Doctor ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $d): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($d['doctor_code'] ?? 'DOC-'.$d['id']) ?></span></td>
                                <td><?= htmlspecialchars($d['name']) ?></td>
                                <td><?= htmlspecialchars($d['department_name'] ?? 'General') ?></td>
                                <td><code><?= htmlspecialchars($d['email']) ?></code></td>
                                <td><code>password123</code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <a href="../doctor-login.php" target="_blank" class="btn btn-primary">Open Doctor Login Page</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
