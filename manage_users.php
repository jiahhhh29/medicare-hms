<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = ""; $message_type = "";

// Handle user role update (for demo purposes, just switching role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid = intval($_POST['user_id']);
    $role = $_POST['role'];
    
    $upd = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $upd->bind_param("si", $role, $uid);
    if ($upd->execute()) {
        $message = "User role updated successfully.";
        $message_type = "success";
    }
}

// Fetch Users
$users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | MediCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dash-main" style="padding:40px 0;"><div class="container">
    <h2 class="text-white mb-4"><i class="bi bi-people text-primary me-2"></i>Manage Users</h2>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['role']==='admin'?'danger':($u['role']==='doctor'?'info':'primary') ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): // Can't change own role easily ?>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-select form-select-sm bg-dark text-white border-secondary" style="width:120px;">
                                        <option value="patient" <?= $u['role']=='patient'?'selected':'' ?>>Patient</option>
                                        <option value="doctor" <?= $u['role']=='doctor'?'selected':'' ?>>Doctor</option>
                                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-sm btn-outline-light">Update</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
