<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacist') {
    header("Location: ../login.php");
    exit;
}

$user_name = $_SESSION['user_name'];
$message = ""; $message_type = "";

// Handle Add Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    
    if (!empty($name)) {
        $ins = $conn->prepare("INSERT INTO pharmacy_medicines (name, price, stock) VALUES (?, ?, ?)");
        $ins->bind_param("sdi", $name, $price, $stock);
        if ($ins->execute()) {
            $message = "Medicine added successfully!";
            $message_type = "success";
        }
    }
}

// Handle Update Order Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $req_id = intval($_POST['request_id']);
    $status = $_POST['status']; // 'Dispensed' or 'Cancelled'
    
    $upd = $conn->prepare("UPDATE pharmacy_requests SET status = ?, dispensed_at = CURRENT_TIMESTAMP WHERE id = ?");
    $upd->bind_param("si", $status, $req_id);
    if ($upd->execute()) {
        $message = "Order status updated to $status.";
        $message_type = "success";
    }
}

// Fetch Medicines
$meds = $conn->query("SELECT * FROM pharmacy_medicines ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch Orders
$orders = $conn->query("
    SELECT pr.*, p.name AS patient_name 
    FROM pharmacy_requests pr 
    JOIN patients p ON pr.patient_id = p.id 
    ORDER BY pr.requested_at DESC
")->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard | MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg dash-navbar">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-capsule-pill" style="-webkit-text-fill-color:var(--success);"></i>
            <span style="background:linear-gradient(135deg,#fff,var(--success));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">MediCare Pharmacy</span>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="dispense_bill.php">Dispense & Bill</a></li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="user-pill d-none d-md-flex">
                <div class="user-avatar bg-success"><?= $avatar_letter ?></div>
                <?= htmlspecialchars($user_name) ?>
            </div>
            <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dash-main" style="padding:40px 0;"><div class="container">
    <h2 class="text-white mb-4"><i class="bi bi-capsule-pill text-success me-2"></i>Pharmacy Dashboard</h2>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add Medicine Form -->
        <div class="col-md-4">
            <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3">Add Medicine</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-white">Medicine Name</label>
                            <input type="text" name="name" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white">Price (₹)</label>
                            <input type="number" step="0.01" name="price" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white">Initial Stock</label>
                            <input type="number" name="stock" class="form-control bg-dark text-white border-secondary" value="100" required>
                        </div>
                        <button type="submit" name="add_medicine" class="btn btn-success w-100">Add Medicine</button>
                    </form>
                </div>
            </div>
            
            <!-- Medicine List -->
            <div class="card mt-4" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3">Medicine Inventory</h5>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-dark table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meds as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                    <td>₹<?= number_format($m['price'], 2) ?></td>
                                    <td><?= $m['stock'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pharmacy Orders -->
        <div class="col-md-8">
            <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3">Patient Pharmacy Requests</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Medicine Req.</th>
                                    <th>Qty</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($o['medicine_name']) ?></td>
                                    <td><?= $o['quantity'] ?></td>
                                    <td><small><?= htmlspecialchars($o['notes'] ?? '-') ?></small></td>
                                    <td>
                                        <span class="badge bg-<?= $o['status']==='Pending'?'warning':($o['status']==='Dispensed'?'success':'danger') ?>">
                                            <?= $o['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($o['status'] === 'Pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?= $o['id'] ?>">
                                            <button type="submit" name="update_order_status" class="btn btn-sm btn-success" title="Dispense">
                                                <input type="hidden" name="status" value="Dispensed">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?= $o['id'] ?>">
                                            <button type="submit" name="update_order_status" class="btn btn-sm btn-danger" title="Cancel">
                                                <input type="hidden" name="status" value="Cancelled">
                                                <i class="bi bi-x"></i>
                                            </button>
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
        </div>
    </div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
