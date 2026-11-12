<?php
session_start();
require_once "includes/db.php";

$message      = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name        = trim($_POST["name"]);
    $email       = trim($_POST["email"]);
    $password    = $_POST["password"];
    $gender      = $_POST["gender"];
    $dob         = $_POST["date_of_birth"];
    $phone       = trim($_POST["phone"]);
    $address     = trim($_POST["address"]);
    $blood_group = $_POST["blood_group"];

    if (empty($name) || empty($email) || empty($password) || empty($gender) || empty($dob) || empty($phone)) {
        $message      = "Please fill all required fields.";
        $message_type = "danger";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message      = "Email already registered.";
            $message_type = "danger";
        }

        if (empty($message)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $u = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')");
            $u->bind_param("sss", $name, $email, $hashed);

            if ($u->execute()) {
                $user_id = $conn->insert_id;
                $p = $conn->prepare(
                    "INSERT INTO patients (user_id, name, gender, date_of_birth, phone, address, blood_group)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $p->bind_param("issssss", $user_id, $name, $gender, $dob, $phone, $address, $blood_group);
                if ($p->execute()) {
                    $message      = "Registration successful! You can now login.";
                    $message_type = "success";
                } else {
                    $message      = "Patient profile could not be created.";
                    $message_type = "danger";
                }
            } else {
                $message      = "Registration failed. Please try again.";
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
    <title>Patient Registration | MediCare Hospital</title>
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
            max-width: 720px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            padding: 48px 48px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5), 0 0 60px rgba(37,99,235,0.10);
            animation: fadeInUp 0.6s ease both;
        }

        .auth-logo {
            width: 72px; height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            display: flex; align-items: center; justify-content: center;
            font-size: 34px; color: #fff;
            margin: 0 auto 24px;
            box-shadow: 0 12px 32px rgba(37,99,235,0.4);
        }

        .step-indicator {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-bottom: 32px;
        }
        .step-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
        }
        .step-dot.active {
            background: #2563eb;
            width: 24px;
            border-radius: 4px;
        }

        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 20px;
            color: var(--text-dim);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-divider::before, .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.07);
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
            <a href="register.php" class="role-switch-badge active"><i class="bi bi-person me-1"></i>Patient</a>
            <a href="doctor-register.php" class="role-switch-badge"><i class="bi bi-person-badge me-1"></i>Doctor</a>
            <a href="pharmacy-register.php" class="role-switch-badge"><i class="bi bi-capsule me-1"></i>Pharmacy</a>
            <a href="lab-register.php" class="role-switch-badge"><i class="bi bi-eyedropper me-1"></i>Lab Tech</a>
            <a href="admin-register.php" class="role-switch-badge"><i class="bi bi-shield-lock me-1"></i>Admin</a>
        </div>

        <div class="text-center">
            <div class="auth-logo">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h2 class="fw-bold mb-1" style="letter-spacing:-0.5px; color:#fff;">Patient Registration</h2>
            <p style="color:var(--text-muted); font-size:14px;">Register as a MediCare patient for easy appointments & care</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-4 mb-0">
            <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php if ($message_type === 'success'): ?>
        <div class="text-center mt-3">
            <a href="login.php" class="btn btn-primary px-5">
                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($message_type !== 'success'): ?>
        <form method="POST" action="register.php" class="mt-4">

            <div class="section-divider">Personal Information</div>

            <!-- Name -->
            <div class="mb-3">
                <label class="form-label">Full Name *</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-right:none;color:#64748b;">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" name="name" class="form-control"
                           placeholder="Enter your full name" required
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           style="border-left:none;">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label">Email Address *</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-right:none;color:#64748b;">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" name="email" class="form-control"
                               placeholder="your@email.com" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               style="border-left:none;">
                    </div>
                </div>
                <!-- Password -->
                <div class="col-md-6">
                    <label class="form-label">Password *</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-right:none;color:#64748b;">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" name="password" id="regPwd" class="form-control"
                               placeholder="Min 6 characters" minlength="6" required
                               style="border-left:none;border-right:none;">
                        <button type="button" class="input-group-text"
                                style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-left:none;color:#64748b;cursor:pointer;"
                                onclick="toggleRegPwd()">
                            <i class="bi bi-eye" id="regEye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="section-divider">Medical Information</div>

            <div class="row g-3 mb-3">
                <!-- Gender -->
                <div class="col-md-6">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male"   <?php echo (($_POST['gender'] ?? '') === 'Male')   ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other"  <?php echo (($_POST['gender'] ?? '') === 'Other')  ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <!-- DOB -->
                <div class="col-md-6">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" name="date_of_birth" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Phone -->
                <div class="col-md-6">
                    <label class="form-label">Phone Number *</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-right:none;color:#64748b;">
                            <i class="bi bi-phone"></i>
                        </span>
                        <input type="tel" name="phone" class="form-control"
                               placeholder="Enter phone number" required
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               style="border-left:none;">
                    </div>
                </div>
                <!-- Blood Group -->
                <div class="col-md-6">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select Blood Group</option>
                        <?php
                        $bgs = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
                        foreach ($bgs as $bg) {
                            $sel = (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : '';
                            echo "<option value=\"$bg\" $sel>$bg</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label class="form-label">Home Address</label>
                <textarea name="address" class="form-control" rows="2"
                          placeholder="Enter your address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3" style="font-size:15px;">
                <i class="bi bi-person-check me-2"></i>Create Patient Account
            </button>

        </form>
        <?php endif; ?>

        <div class="text-center mt-4" style="font-size:14px; color:var(--text-muted);">
            Already have an account?
            <a href="login.php" style="color:#60a5fa; font-weight:600; text-decoration:none;">
                Login here
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleRegPwd() {
    const f = document.getElementById('regPwd');
    const i = document.getElementById('regEye');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
