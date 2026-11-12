<?php
session_start();
require_once "includes/db.php";

$message = "";
$message_type = "";
$registered_user = null;

// Hospital Admin Verification Code
define('ADMIN_SECURITY_CODE', 'MEDICARE2026');

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name         = trim($_POST["name"] ?? '');
    $email        = trim($_POST["email"] ?? '');
    $password     = $_POST["password"] ?? '';
    $confirm_pwd  = $_POST["confirm_password"] ?? '';
    $designation  = trim($_POST["designation"] ?? 'Hospital Administrator');
    $security_key = trim($_POST["security_key"] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($security_key)) {
        $message      = "Please fill in all required fields including the Admin Security Key.";
        $message_type = "danger";
    } elseif ($security_key !== ADMIN_SECURITY_CODE && $security_key !== 'admin123') {
        $message      = "Invalid Admin Security Key. Please provide the authorized hospital administrator key.";
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
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($stmt->execute()) {
                $registered_user = [
                    'name'        => $name,
                    'email'       => $email,
                    'designation' => $designation
                ];
                $message      = "Hospital Administrator account created successfully! Welcome, $name.";
                $message_type = "success";
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
    <title>Admin Registration | MediCare Hospital</title>
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
            max-width: 680px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5), 0 0 60px rgba(220,38,38,0.12);
            animation: fadeInUp 0.6s ease both;
        }

        .auth-logo {
            width: 70px; height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, #7c3aed, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: #fff;
            margin: 0 auto 20px;
            box-shadow: 0 12px 32px rgba(124,58,237,0.4);
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
            background: #7c3aed;
            color: #fff;
            border-color: #7c3aed;
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
            <a href="doctor-register.php" class="role-switch-badge"><i class="bi bi-person-badge me-1"></i>Doctor</a>
            <a href="pharmacy-register.php" class="role-switch-badge"><i class="bi bi-capsule me-1"></i>Pharmacy</a>
            <a href="lab-register.php" class="role-switch-badge"><i class="bi bi-eyedropper me-1"></i>Lab Tech</a>
            <a href="admin-register.php" class="role-switch-badge active"><i class="bi bi-shield-lock me-1"></i>Admin</a>
        </div>

        <div class="text-center">
            <div class="auth-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h2 class="fw-bold mb-1" style="letter-spacing:-0.5px; color:#fff;">Administrator Registration</h2>
            <p style="color:var(--text-muted); font-size:14px;">Onboard hospital management, IT & operations administration</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-3 mb-0" role="alert">
            <i class="bi bi-<?php echo $message_type === 'danger' ? 'exclamation-circle' : 'check-circle-fill'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($registered_user): ?>
        <div class="p-4 rounded-4 mt-4 text-center" style="background:rgba(124,58,237,0.1); border:1px solid rgba(124,58,237,0.3);">
            <div class="display-6 text-warning mb-2"><i class="bi bi-shield-fill-check"></i></div>
            <h4 class="text-white fw-bold mb-1">Admin Access Granted!</h4>
            <p class="text-muted" style="font-size:14px;">Your administrator credentials are now active on the system.</p>
            
            <div class="d-inline-flex flex-column align-items-center bg-black bg-opacity-40 p-3 rounded-3 my-2 border border-secondary border-opacity-25">
                <span class="text-white fw-bold"><?= htmlspecialchars($registered_user['name']) ?></span>
                <small class="text-info"><?= htmlspecialchars($registered_user['email']) ?></small>
                <span class="badge bg-danger mt-1">Role: Admin (<?= htmlspecialchars($registered_user['designation']) ?>)</span>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="admin-login.php" class="btn btn-primary px-4 py-2" style="background:#7c3aed; border-color:#7c3aed;">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Admin Portal
                </a>
            </div>
        </div>
        <?php else: ?>

        <form method="POST" action="admin-register.php" class="mt-4">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Administrator Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Michael Scott" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Official Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="admin.staff@medicare.com" required
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

                <div class="col-md-6">
                    <label class="form-label" style="font-size:13px;">Designation / Title</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                        <select name="designation" class="form-select">
                            <option value="Hospital Director">Hospital Director</option>
                            <option value="Operations Manager" selected>Operations Manager</option>
                            <option value="Clinical Systems Administrator">Clinical Systems Administrator</option>
                            <option value="Medical Superintendent">Medical Superintendent</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <label class="form-label" style="font-size:13px;">Admin Security Key <span class="text-danger">*</span></label>
                        <span class="badge bg-secondary-subtle text-info border" style="font-size:10px;">Default: MEDICARE2026</span>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key-fill text-warning"></i></span>
                        <input type="password" name="security_key" class="form-control" placeholder="Enter hospital security key" required
                               value="<?php echo htmlspecialchars($_POST['security_key'] ?? 'MEDICARE2026'); ?>">
                    </div>
                </div>
            </div>

            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary border-opacity-25 mt-4">
                <small class="text-muted" style="font-size:12px;">
                    <i class="bi bi-shield-check text-warning me-1"></i>
                    Admin accounts require verification via hospital security key (default test key: <b>MEDICARE2026</b> or <b>admin123</b>).
                </small>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mt-4 text-white" style="font-size:15px; font-weight:600; background:#7c3aed; border-color:#7c3aed;">
                <i class="bi bi-shield-plus me-2"></i>Register Administrator Account
            </button>

        </form>
        <?php endif; ?>

        <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25" style="font-size:13px; color:var(--text-muted);">
            Already have an administrator account?
            <a href="admin-login.php" style="color:#c084fc; font-weight:600; text-decoration:none;">
                Admin Login &rarr;
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
