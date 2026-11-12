<?php
session_start();
require_once "includes/db.php";

$message = "";
$message_type = "";
$registered_doctor = null;

// Fetch departments for dropdown
$departments = $conn->query("SELECT id, name, code, icon FROM departments ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name           = trim($_POST["name"] ?? '');
    $email          = trim($_POST["email"] ?? '');
    $password       = $_POST["password"] ?? '';
    $confirm_pwd    = $_POST["confirm_password"] ?? '';
    $department_id  = intval($_POST["department_id"] ?? 0);
    $specialization = trim($_POST["specialization"] ?? '');
    $experience     = intval($_POST["experience"] ?? 0);
    $phone          = trim($_POST["phone"] ?? '');
    $room_number    = trim($_POST["room_number"] ?? '');

    // Ensure single clean Dr. prefix
    $name = 'Dr. ' . preg_replace('/^(dr\.?\s*)+/i', '', trim($name));

    if (empty($name) || empty($email) || empty($password) || empty($specialization) || $department_id <= 0) {
        $message      = "Please fill in all required fields and select a department.";
        $message_type = "danger";
    } elseif ($password !== $confirm_pwd) {
        $message      = "Passwords do not match. Please re-enter.";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message      = "Password should be at least 6 characters long.";
        $message_type = "danger";
    } else {
        // Check if email already registered
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $message      = "An account with this email address already exists. Please login or use a different email.";
            $message_type = "danger";
        } else {
            // Generate next Doctor Code: DOC-XXX
            $maxRes = $conn->query("SELECT MAX(id) AS max_id FROM doctors");
            $nextNum = ($maxRes && $row = $maxRes->fetch_assoc()) ? (intval($row['max_id']) + 1) : 1;
            $doctor_code = sprintf("DOC-%03d", $nextNum);

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // 1. Insert into users
            $uStmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'doctor')");
            $uStmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($uStmt->execute()) {
                $user_id = $uStmt->insert_id;

                // 2. Insert into doctors
                $dStmt = $conn->prepare("
                    INSERT INTO doctors (user_id, doctor_code, name, department_id, specialization, experience, phone, room_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $dStmt->bind_param("issisiss", $user_id, $doctor_code, $name, $department_id, $specialization, $experience, $phone, $room_number);

                if ($dStmt->execute()) {
                    $doc_id = $dStmt->insert_id;

                    // Get department name
                    $deptName = "General Medicine";
                    foreach ($departments as $d) {
                        if ($d['id'] == $department_id) {
                            $deptName = $d['name'];
                            break;
                        }
                    }

                    $registered_doctor = [
                        'name'            => $name,
                        'email'           => $email,
                        'doctor_code'     => $doctor_code,
                        'department_name' => $deptName,
                        'specialization'  => $specialization,
                        'doc_id'          => $doc_id
                    ];

                    $message      = "Congratulations $name! Your doctor account has been successfully created.";
                    $message_type = "success";
                } else {
                    $message      = "Could not save doctor profile: " . $conn->error;
                    $message_type = "danger";
                }
            } else {
                $message      = "Registration failed: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration | MediCare Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            position: relative;
            z-index: 1;
        }

        .auth-card {
            width: 100%;
            max-width: 760px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5), 0 0 60px rgba(37,99,235,0.12);
            animation: fadeInUp 0.6s ease both;
        }

        .auth-logo {
            width: 70px; height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: #fff;
            margin: 0 auto 20px;
            box-shadow: 0 12px 32px rgba(37,99,235,0.4);
        }

        .input-group-text {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.10) !important;
            border-right: none !important;
            color: #64748b !important;
        }

        .input-group .form-control, .input-group .form-select {
            border-left: none !important;
        }

        .form-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #60a5fa;
            margin: 20px 0 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        .role-switch-badge {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #cbd5e1;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .role-switch-badge:hover, .role-switch-badge.active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .back-home {
            position: fixed;
            top: 20px;
            left: 24px;
            z-index: 100;
        }
    </style>
</head>
<body>

<a href="index.php" class="back-home btn btn-outline-light btn-sm">
    <i class="bi bi-arrow-left me-1"></i> Home
</a>

<div class="auth-page">
    <div class="auth-card">

        <!-- Staff Role Switcher -->
        <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
            <a href="register.php" class="role-switch-badge"><i class="bi bi-person me-1"></i>Patient</a>
            <a href="doctor-register.php" class="role-switch-badge active"><i class="bi bi-person-badge me-1"></i>Doctor</a>
            <a href="pharmacy-register.php" class="role-switch-badge"><i class="bi bi-capsule me-1"></i>Pharmacy</a>
            <a href="lab-register.php" class="role-switch-badge"><i class="bi bi-eyedropper me-1"></i>Lab Tech</a>
            <a href="admin-register.php" class="role-switch-badge"><i class="bi bi-shield-lock me-1"></i>Admin</a>
        </div>

        <div class="text-center">
            <div class="auth-logo">
                <i class="bi bi-hospital-fill"></i>
            </div>
            <h2 class="fw-bold mb-1" style="letter-spacing:-0.5px; color:#fff;">Doctor Registration</h2>
            <p style="color:var(--text-muted); font-size:14px;">Join MediCare Hospital medical staff & start consultations</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-3 mb-0" role="alert">
            <i class="bi bi-<?php echo $message_type === 'danger' ? 'exclamation-circle' : 'check-circle-fill'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($registered_doctor): ?>
        <!-- Success Confirmation Card -->
        <div class="p-4 rounded-4 mt-4 text-center" style="background:rgba(37,99,235,0.1); border:1px solid rgba(37,99,235,0.3);">
            <div class="display-6 text-success mb-2"><i class="bi bi-check-circle-fill"></i></div>
            <h4 class="text-white fw-bold mb-1">Welcome to MediCare!</h4>
            <p class="text-muted" style="font-size:14px;">Your official Doctor Profile is ready for appointments.</p>
            
            <div class="d-inline-flex flex-column align-items-center bg-black bg-opacity-40 p-3 rounded-3 my-2 border border-secondary border-opacity-25">
                <span style="font-size:11px; text-transform:uppercase; color:#94a3b8; font-weight:700;">Your Doctor ID</span>
                <span class="badge bg-primary fs-5 mt-1 font-monospace px-3 py-2"><?= htmlspecialchars($registered_doctor['doctor_code']) ?></span>
                <small class="text-muted mt-2" style="font-size:12px;">Login using this ID or your email <b><?= htmlspecialchars($registered_doctor['email']) ?></b></small>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="doctor-login.php" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Proceed to Doctor Login
                </a>
            </div>
        </div>
        <?php else: ?>

        <!-- Doctor Registration Form -->
        <form method="POST" action="doctor-register.php" class="mt-4">

            <!-- 1. Account Details -->
            <div class="form-section-title"><i class="bi bi-person-fill"></i> Account Credentials</div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Full Name (with Dr. prefix) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="name" class="form-control" placeholder="Dr. John Watson" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Official Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="doctor@medicare.com" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                    </div>
                </div>
            </div>

            <!-- 2. Medical & Department Specialization -->
            <div class="form-section-title"><i class="bi bi-building"></i> Department & Medical Specialization</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Hospital Department <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-building-fill-gear"></i></span>
                        <select name="department_id" class="form-select" required>
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($dept['name']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Specialization <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-award"></i></span>
                        <input type="text" name="specialization" class="form-control" placeholder="e.g. Interventional Cardiology, Pediatric Surgery" required
                               value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-size:13px;">Experience (in Years)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-clock-history"></i></span>
                        <input type="number" name="experience" min="0" max="60" class="form-control" placeholder="e.g. 8"
                               value="<?php echo htmlspecialchars($_POST['experience'] ?? '5'); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-size:13px;">Contact Phone</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-size:13px;">Consultation Room / Location</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" name="room_number" class="form-control" placeholder="Room 204, 2nd Floor"
                               value="<?php echo htmlspecialchars($_POST['room_number'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary border-opacity-25 mt-4">
                <small class="text-muted" style="font-size:12px;">
                    <i class="bi bi-info-circle text-info me-1"></i>
                    A unique <b>Doctor ID (e.g. DOC-012)</b> will be automatically assigned to you upon registration. You can log in using either your Doctor ID or Email.
                </small>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mt-4" style="font-size:15px; font-weight:600;">
                <i class="bi bi-person-plus-fill me-2"></i>Complete Doctor Registration
            </button>

        </form>
        <?php endif; ?>

        <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25" style="font-size:13px; color:var(--text-muted);">
            Already have a doctor account?
            <a href="doctor-login.php" style="color:#60a5fa; font-weight:600; text-decoration:none;">
                Doctor Login Portal &rarr;
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
