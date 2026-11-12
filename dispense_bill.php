<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacist') {
    header("Location: ../pharmacy-login.php");
    exit;
}

$user_name = $_SESSION['user_name'];
$message = ""; $message_type = "";

// Handle Generate Bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_bill'])) {
    $patient_id = intval($_POST['patient_id']);
    $medicine_ids = $_POST['medicine_id']; // Array
    $quantities = $_POST['quantity']; // Array
    
    if ($patient_id > 0 && !empty($medicine_ids) && is_array($medicine_ids)) {
        
        $conn->begin_transaction();
        
        try {
            $total_amount = 0;
            $items_dispensed = [];
            $desc_parts = [];
            
            for ($i = 0; $i < count($medicine_ids); $i++) {
                $med_id = intval($medicine_ids[$i]);
                $qty = intval($quantities[$i]);
                
                if ($med_id > 0 && $qty > 0) {
                    $med_stmt = $conn->prepare("SELECT name, price, stock FROM pharmacy_medicines WHERE id = ?");
                    $med_stmt->bind_param("i", $med_id);
                    $med_stmt->execute();
                    $med_res = $med_stmt->get_result();
                    
                    if ($med_res->num_rows == 1) {
                        $med = $med_res->fetch_assoc();
                        
                        if ($med['stock'] >= $qty) {
                            $item_total = $med['price'] * $qty;
                            $total_amount += $item_total;
                            $med_name = $med['name'];
                            
                            // Update stock
                            $upd_stock = $conn->prepare("UPDATE pharmacy_medicines SET stock = stock - ? WHERE id = ?");
                            $upd_stock->bind_param("ii", $qty, $med_id);
                            $upd_stock->execute();
                            
                            // Insert into pharmacy_requests (record it as directly dispensed)
                            $status = 'Dispensed';
                            $notes = "Direct Bill Generation";
                            $ins_req = $conn->prepare("INSERT INTO pharmacy_requests (patient_id, medicine_name, quantity, notes, status, dispensed_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                            $ins_req->bind_param("isiss", $patient_id, $med_name, $qty, $notes, $status);
                            $ins_req->execute();
                            
                            $items_dispensed[] = [
                                'med_name' => $med_name,
                                'quantity' => $qty,
                                'unit_price' => $med['price'],
                                'item_total' => $item_total
                            ];
                            
                            $desc_parts[] = $med_name . " (Qty: " . $qty . ")";
                        } else {
                            throw new Exception("Insufficient stock for " . $med['name'] . ". Available: " . $med['stock']);
                        }
                    } else {
                        throw new Exception("Medicine not found.");
                    }
                }
            }
            
            if (empty($items_dispensed)) {
                throw new Exception("No valid medicines were selected.");
            }
            
            // Insert into bills
            $desc = "Pharmacy Bill: " . implode(", ", $desc_parts);
            $payment_status = 'Pending';
            $ins_bill = $conn->prepare("INSERT INTO bills (patient_id, amount, description, payment_status) VALUES (?, ?, ?, ?)");
            $ins_bill->bind_param("idss", $patient_id, $total_amount, $desc, $payment_status);
            $ins_bill->execute();
            
            $conn->commit();
            $message = "Bill successfully generated! Total Amount: ₹" . number_format($total_amount, 2);
            $message_type = "success";
            
            // Store details for receipt display
            $patient_name = $conn->query("SELECT name FROM patients WHERE id = $patient_id")->fetch_object()->name;
            $generated_bill = [
                'patient_name' => $patient_name,
                'items' => $items_dispensed,
                'total_amount' => $total_amount
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to generate bill: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Please fill all fields correctly.";
        $message_type = "danger";
    }
}

// Fetch all patients
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
// Fetch medicines
$medicines = $conn->query("SELECT id, name, price, stock FROM pharmacy_medicines WHERE stock > 0 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$avatar_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Dispense & Bill | MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-receipt, .print-receipt * {
                visibility: visible;
            }
            .print-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .btn-print {
                display: none !important;
            }
        }
    </style>
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
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="dispense_bill.php">Dispense & Bill</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white m-0"><i class="bi bi-receipt text-success me-2"></i>Dispense Medicine & Generate Bill</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    
    <?php if ($message_type === "success" && isset($generated_bill)): ?>
    <div class="card mb-4 print-receipt" style="background:#fff; color:#000; border-radius:8px; max-width:600px; margin:auto;">
        <div class="card-body p-4 text-center">
            <h4 class="mb-1 fw-bold">MediCare Pharmacy Receipt</h4>
            <p class="text-muted mb-4"><small><?= date("Y-m-d H:i:s") ?></small></p>
            
            <table class="table table-bordered text-start">
                <tr>
                    <th style="width: 40%;">Patient Name:</th>
                    <td><?= htmlspecialchars($generated_bill['patient_name']) ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($generated_bill['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['med_name']) ?></td>
                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td>₹<?= number_format($item['unit_price'], 2) ?></td>
                                    <td>₹<?= number_format($item['item_total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th>Total Amount:</th>
                    <td class="fw-bold text-success">₹<?= number_format($generated_bill['total_amount'], 2) ?></td>
                </tr>
            </table>
            
            <button class="btn btn-primary mt-2 btn-print" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print Receipt</button>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>

    <div class="card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;max-width:600px;margin:auto;">
        <div class="card-body p-4">
            <form method="POST">
                
                <div class="mb-3">
                    <label class="form-label text-white">Select Patient</label>
                    <select name="patient_id" class="form-select bg-dark text-white border-secondary" required>
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="medicinesContainer">
                    <div class="row mb-3 medicine-row">
                        <div class="col-md-7">
                            <label class="form-label text-white">Select Medicine</label>
                            <select name="medicine_id[]" class="form-select bg-dark text-white border-secondary med-select" onchange="calculateTotal()" required>
                                <option value="" data-price="0">-- Choose Medicine --</option>
                                <?php foreach ($medicines as $m): ?>
                                    <option value="<?= $m['id'] ?>" data-price="<?= $m['price'] ?>">
                                        <?= htmlspecialchars($m['name']) ?> (₹<?= number_format($m['price'], 2) ?> | Stock: <?= $m['stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white">Quantity</label>
                            <input type="number" name="quantity[]" class="form-control bg-dark text-white border-secondary qty-input" min="1" value="1" oninput="calculateTotal()" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger w-100 remove-btn" onclick="removeRow(this)" disabled><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="addMedicineRow()">
                        <i class="bi bi-plus-circle me-1"></i>Add Another Medicine
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-white">Total Amount (₹)</label>
                    <input type="text" id="totalAmount" class="form-control bg-dark text-white border-secondary fw-bold" style="font-size: 1.2rem;" readonly value="0.00">
                </div>
                
                <button type="submit" name="generate_bill" class="btn btn-success w-100 py-3" style="font-size: 16px;">
                    <i class="bi bi-file-earmark-check me-2"></i>Generate Bill & Dispense
                </button>
            </form>
        </div>
    </div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calculateTotal() {
    var total = 0;
    var rows = document.querySelectorAll('.medicine-row');
    rows.forEach(function(row) {
        var select = row.querySelector('.med-select');
        var qtyInput = row.querySelector('.qty-input');
        
        var price = 0;
        if(select.selectedIndex >= 0) {
            price = parseFloat(select.options[select.selectedIndex].getAttribute('data-price')) || 0;
        }
        var qty = parseInt(qtyInput.value) || 0;
        total += (price * qty);
    });
    document.getElementById('totalAmount').value = total.toFixed(2);
}

function addMedicineRow() {
    var container = document.getElementById('medicinesContainer');
    var firstRow = container.querySelector('.medicine-row');
    var newRow = firstRow.cloneNode(true);
    
    // Reset values
    newRow.querySelector('.med-select').selectedIndex = 0;
    newRow.querySelector('.qty-input').value = 1;
    
    // Enable remove button
    newRow.querySelector('.remove-btn').disabled = false;
    
    container.appendChild(newRow);
    updateRemoveButtons();
    calculateTotal();
}

function removeRow(btn) {
    var row = btn.closest('.medicine-row');
    row.remove();
    updateRemoveButtons();
    calculateTotal();
}

function updateRemoveButtons() {
    var rows = document.querySelectorAll('.medicine-row');
    rows.forEach(function(row, index) {
        var btn = row.querySelector('.remove-btn');
        if(rows.length === 1) {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    });
}
</script>
</body>
</html>
