<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = ""; $message_type = "";

$ps = $conn->prepare("SELECT id, name, phone FROM patients WHERE user_id = ? LIMIT 1");
$ps->bind_param("i", $user_id);
$ps->execute();
$prow = $ps->get_result();
if ($prow->num_rows === 0) die("Patient profile not found.");
$patient    = $prow->fetch_assoc();
$patient_id = $patient['id'];
$patient_phone = $patient['phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill'])) {
    $bill_id = intval($_POST['bill_id']);
    $amount = floatval($_POST['amount']);
    $payment_id = trim($_POST['razorpay_payment_id']);
    
    if (!empty($payment_id)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update bill status
            $upd = $conn->prepare("UPDATE bills SET payment_status = 'Paid' WHERE id = ? AND patient_id = ?");
            $upd->bind_param("ii", $bill_id, $patient_id);
            $upd->execute();
            
            // Insert payment record
            $ins = $conn->prepare("INSERT INTO payments (patient_id, reference_type, reference_id, amount, razorpay_payment_id, payment_status) VALUES (?, 'Bill', ?, ?, ?, 'Success')");
            $ins->bind_param("iids", $patient_id, $bill_id, $amount, $payment_id);
            $ins->execute();
            
            $conn->commit();
            $message = "Payment successful!";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Payment failed to record. Please contact support.";
            $message_type = "danger";
        }
    }
}

$bs = $conn->prepare("SELECT * FROM bills WHERE patient_id = ? ORDER BY bill_date DESC");
$bs->bind_param("i", $patient_id);
$bs->execute();
$bills = $bs->get_result()->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills | MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dash-main { position:relative; z-index:1; padding:40px 0 70px; }
        .bill-card {
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            border-radius:16px; padding:24px 26px; margin-bottom:14px;
            transition:all 0.25s; position:relative; overflow:hidden;
        }
        .bill-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:linear-gradient(180deg,#3b82f6,#2563eb); }
        .bill-card:hover { border-color:rgba(59,130,246,0.35); background:rgba(59,130,246,0.04); transform:translateX(4px); }
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
            <li class="breadcrumb-item active" style="color:var(--text-muted);">My Bills</li>
        </ol>
    </nav>

    <div class="mb-5" style="animation:fadeInUp 0.4s ease both;">
        <h2 class="fw-bold mb-1" style="color:#fff;letter-spacing:-0.5px;">
            <i class="bi bi-receipt me-2 text-primary"></i>My Bills & Payments
        </h2>
        <p style="color:var(--text-muted);font-size:14px;margin:0;">
            View your hospital bills and pay securely online.
        </p>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
        <i class="bi bi-<?= $message_type==='success'?'check-circle-fill':'exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div style="animation:fadeInUp 0.4s 0.2s ease both;">
        <?php if (empty($bills)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:70px 20px;">
            <i class="bi bi-receipt-cutoff" style="font-size:56px;color:var(--text-dim);display:block;margin-bottom:16px;"></i>
            <h5 style="color:var(--text-muted);">No Bills Found</h5>
            <p style="color:var(--text-dim);font-size:14px;">You have no hospital bills at the moment.</p>
        </div></div>
        <?php else: ?>
        <?php foreach ($bills as $i => $bill): 
            $status = $bill['payment_status'] ?? 'Pending';
            $isPaid = ($status === 'Paid');
        ?>
        <div class="bill-card" style="animation:fadeInUp <?= 0.3+$i*0.07 ?>s ease both;">
            <div class="d-flex align-items-start gap-3 flex-wrap">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.25);display:flex;align-items:center;justify-content:center;font-size:20px;color:#3b82f6;flex-shrink:0;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:#fff;">
                                ₹<?= number_format($bill['amount'], 2) ?>
                            </h5>
                            <span style="font-size:12px;color:var(--text-dim);">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= !empty($bill['bill_date']) ? date('d M Y', strtotime($bill['bill_date'])) : '—' ?>
                            </span>
                        </div>
                        <span class="badge" style="background:<?= $isPaid ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)' ?>;border:1px solid <?= $isPaid ? 'rgba(16,185,129,0.3)' : 'rgba(245,158,11,0.3)' ?>;color:<?= $isPaid ? '#34d399' : '#fbbf24' ?>;font-size:11px;">
                            <?= $status ?>
                        </span>
                    </div>
                    <?php if (!empty($bill['description'])): ?>
                    <p class="mt-2 mb-0" style="font-size:14px;color:var(--text-muted);">
                        <?= htmlspecialchars($bill['description']) ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if (!$isPaid): ?>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm px-4 pay-btn" data-id="<?= $bill['id'] ?>" data-amount="<?= $bill['amount'] ?>">
                            <i class="bi bi-credit-card me-1"></i> Pay Now
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div></div>

<!-- Hidden Payment Form -->
<form id="paymentForm" method="POST" action="">
    <input type="hidden" name="pay_bill" value="1">
    <input type="hidden" name="bill_id" id="pay_bill_id" value="">
    <input type="hidden" name="amount" id="pay_amount" value="">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.querySelectorAll('.pay-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const billId = this.dataset.id;
        const amount = parseFloat(this.dataset.amount);
        
        var options = {
            "key": "rzp_test_YourTestKeyHere", // Enter the Key ID generated from the Dashboard
            "amount": amount * 100, // Amount is in currency subunits. Default currency is INR.
            "currency": "INR",
            "name": "MediCare Hospital",
            "description": "Hospital Bill Payment",
            "image": "https://cdn-icons-png.flaticon.com/512/2966/2966327.png",
            "handler": function (response){
                document.getElementById('pay_bill_id').value = billId;
                document.getElementById('pay_amount').value = amount;
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('paymentForm').submit();
            },
            "prefill": {
                "name": "<?= addslashes(htmlspecialchars($user_name)) ?>",
                "email": "<?= addslashes(htmlspecialchars($_SESSION['user_email'] ?? '')) ?>",
                "contact": "<?= addslashes(htmlspecialchars($patient_phone)) ?>"
            },
            "theme": {
                "color": "#2563eb"
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.open();
    });
});
</script>
</body>
</html>
