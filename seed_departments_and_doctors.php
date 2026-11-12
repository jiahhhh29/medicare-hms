<?php
/**
 * Setup script for Departments and Demo Doctors
 */
require_once __DIR__ . '/includes/db.php';

echo "Database: " . ($database ?? 'unknown') . "\n";

// 1. Create departments table
$createDepartmentsSql = "CREATE TABLE IF NOT EXISTS departments (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(150) NOT NULL UNIQUE,
    code           VARCHAR(50) NOT NULL UNIQUE,
    description    TEXT,
    icon           VARCHAR(50) DEFAULT 'bi-hospital-fill',
    head_doctor_id INT NULL,
    status         ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($createDepartmentsSql)) {
    echo "✓ departments table created / verified.\n";
} else {
    echo "❌ departments table error: " . $conn->error . "\n";
}

// 2. Check and alter doctors table to include department_id and doctor_code
$cols = [];
$cRes = $conn->query("SHOW COLUMNS FROM doctors");
while ($r = $cRes->fetch_assoc()) {
    $cols[] = $r['Field'];
}

if (!in_array('department_id', $cols)) {
    $conn->query("ALTER TABLE doctors ADD COLUMN department_id INT NULL AFTER specialization");
    echo "✓ Added department_id column to doctors.\n";
}

if (!in_array('doctor_code', $cols)) {
    $conn->query("ALTER TABLE doctors ADD COLUMN doctor_code VARCHAR(50) NULL AFTER user_id");
    echo "✓ Added doctor_code column to doctors.\n";
}

if (!in_array('phone', $cols)) {
    $conn->query("ALTER TABLE doctors ADD COLUMN phone VARCHAR(25) NULL");
    echo "✓ Added phone column to doctors.\n";
}

if (!in_array('room_number', $cols)) {
    $conn->query("ALTER TABLE doctors ADD COLUMN room_number VARCHAR(50) NULL");
    echo "✓ Added room_number column to doctors.\n";
}

// 3. Departments Demo Data
$departments = [
    [
        'name'        => 'Cardiology',
        'code'        => 'CARD',
        'icon'        => 'bi-heart-pulse-fill',
        'description' => 'Comprehensive cardiac care, diagnosis of cardiovascular diseases, ECG, angioplasty, and cardiac surgery.'
    ],
    [
        'name'        => 'Neurology',
        'code'        => 'NEUR',
        'icon'        => 'bi-activity',
        'description' => 'Advanced neurosciences for brain, spinal cord, nerve disorders, stroke rehabilitation, and neurosurgery.'
    ],
    [
        'name'        => 'Orthopedics',
        'code'        => 'ORTH',
        'icon'        => 'bi-bandaid-fill',
        'description' => 'Bone and joint care, trauma management, arthroscopy, joint replacement, and sports medicine.'
    ],
    [
        'name'        => 'Pediatrics',
        'code'        => 'PED',
        'icon'        => 'bi-emoji-smile-fill',
        'description' => 'Child health and development, newborn intensive care (NICU), immunizations, and pediatric surgeries.'
    ],
    [
        'name'        => 'Dermatology',
        'code'        => 'DERM',
        'icon'        => 'bi-person-badge-fill',
        'description' => 'Specialized skin, hair, and nail disorder treatments, medical cosmetology, and allergy therapies.'
    ],
    [
        'name'        => 'General Medicine',
        'code'        => 'GEN',
        'icon'        => 'bi-clipboard2-pulse-fill',
        'description' => 'Primary healthcare, internal medicine, fever and infection treatment, and chronic disease management.'
    ],
    [
        'name'        => 'Oncology',
        'code'        => 'ONC',
        'icon'        => 'bi-shield-shaded',
        'description' => 'Comprehensive cancer care including chemotherapy, surgical oncology, screening, and palliative care.'
    ],
    [
        'name'        => 'Gynecology & Obstetrics',
        'code'        => 'GYN',
        'icon'        => 'bi-gender-female',
        'description' => 'Complete women’s health, maternity & delivery suites, prenatal diagnostics, and reproductive medicine.'
    ],
    [
        'name'        => 'ENT (Otolaryngology)',
        'code'        => 'ENT',
        'icon'        => 'bi-soundwave',
        'description' => 'Expert diagnosis and surgical management of ear, nose, throat, head, neck, and sinus conditions.'
    ],
    [
        'name'        => 'Radiology & Imaging',
        'code'        => 'RAD',
        'icon'        => 'bi-camera-fill',
        'description' => 'High-resolution imaging including MRI, CT scan, digital X-Ray, ultrasound, and 24/7 diagnostics.'
    ]
];

$deptMap = []; // code => id

foreach ($departments as $d) {
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $d['code']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $deptId = $row['id'];
        // Update details
        $uStmt = $conn->prepare("UPDATE departments SET name = ?, icon = ?, description = ? WHERE id = ?");
        $uStmt->bind_param("sssi", $d['name'], $d['icon'], $d['description'], $deptId);
        $uStmt->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO departments (name, code, icon, description, status) VALUES (?, ?, ?, ?, 'Active')");
        $ins->bind_param("ssss", $d['name'], $d['code'], $d['icon'], $d['description']);
        $ins->execute();
        $deptId = $ins->insert_id;
    }
    $deptMap[$d['code']] = $deptId;
    echo "✓ Department: {$d['name']} (ID: $deptId)\n";
}

// 4. Demo Doctors List
// Common standard demo password is "password123"
$hashedPassword = password_hash('password123', PASSWORD_BCRYPT);

$doctorsData = [
    [
        'code'           => 'DOC-001',
        'name'           => 'Dr. Rajesh Kumar',
        'email'          => 'rajesh@medicare.com',
        'dept_code'      => 'CARD',
        'specialization' => 'Cardiology',
        'experience'     => 14,
        'phone'          => '+91 98201 11221',
        'room'           => 'Room 101, 1st Floor'
    ],
    [
        'code'           => 'DOC-002',
        'name'           => 'Dr. Sarah Jenkins',
        'email'          => 'sarah.cardio@medicare.com',
        'dept_code'      => 'CARD',
        'specialization' => 'Interventional Cardiology',
        'experience'     => 10,
        'phone'          => '+91 98201 11222',
        'room'           => 'Room 102, 1st Floor'
    ],
    [
        'code'           => 'DOC-003',
        'name'           => 'Dr. Vikram Malhotra',
        'email'          => 'vikram.neuro@medicare.com',
        'dept_code'      => 'NEUR',
        'specialization' => 'Neurology',
        'experience'     => 12,
        'phone'          => '+91 98202 22331',
        'room'           => 'Room 205, 2nd Floor'
    ],
    [
        'code'           => 'DOC-004',
        'name'           => 'Dr. David Miller',
        'email'          => 'david.ortho@medicare.com',
        'dept_code'      => 'ORTH',
        'specialization' => 'Orthopedics & Joint Replacement',
        'experience'     => 15,
        'phone'          => '+91 98203 33441',
        'room'           => 'Room 310, 3rd Floor'
    ],
    [
        'code'           => 'DOC-005',
        'name'           => 'Dr. Ananya Sen',
        'email'          => 'ananya.pedia@medicare.com',
        'dept_code'      => 'PED',
        'specialization' => 'Pediatrics',
        'experience'     => 9,
        'phone'          => '+91 98204 44551',
        'room'           => 'Room 115, 1st Floor'
    ],
    [
        'code'           => 'DOC-006',
        'name'           => 'Dr. Priya Sharma',
        'email'          => 'priya@medicare.com',
        'dept_code'      => 'DERM',
        'specialization' => 'Dermatology & Cosmetology',
        'experience'     => 8,
        'phone'          => '+91 98205 55661',
        'room'           => 'Room 218, 2nd Floor'
    ],
    [
        'code'           => 'DOC-007',
        'name'           => 'Dr. Arjun Mehta',
        'email'          => 'arjun.med@medicare.com',
        'dept_code'      => 'GEN',
        'specialization' => 'General Medicine',
        'experience'     => 11,
        'phone'          => '+91 98206 66771',
        'room'           => 'Room 105, 1st Floor'
    ],
    [
        'code'           => 'DOC-008',
        'name'           => 'Dr. Sunita Rao',
        'email'          => 'sunita.onco@medicare.com',
        'dept_code'      => 'ONC',
        'specialization' => 'Medical Oncology',
        'experience'     => 16,
        'phone'          => '+91 98207 77881',
        'room'           => 'Room 402, 4th Floor'
    ],
    [
        'code'           => 'DOC-009',
        'name'           => 'Dr. Kavita Reddy',
        'email'          => 'kavita.gyn@medicare.com',
        'dept_code'      => 'GYN',
        'specialization' => 'Gynecology & Obstetrics',
        'experience'     => 13,
        'phone'          => '+91 98208 88991',
        'room'           => 'Room 220, 2nd Floor'
    ],
    [
        'code'           => 'DOC-010',
        'name'           => 'Dr. Robert Taylor',
        'email'          => 'robert.ent@medicare.com',
        'dept_code'      => 'ENT',
        'specialization' => 'ENT (Otolaryngology)',
        'experience'     => 10,
        'phone'          => '+91 98209 99001',
        'room'           => 'Room 112, 1st Floor'
    ],
    [
        'code'           => 'DOC-011',
        'name'           => 'Dr. Alok Verma',
        'email'          => 'alok.radio@medicare.com',
        'dept_code'      => 'RAD',
        'specialization' => 'Radiology & Imaging',
        'experience'     => 7,
        'phone'          => '+91 98210 00111',
        'room'           => 'Diagnostic Wing, Ground Floor'
    ]
];

echo "\n--- Seeding Doctors ---\n";
foreach ($doctorsData as $d) {
    // 1. Ensure user exists
    $uStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $uStmt->bind_param("s", $d['email']);
    $uStmt->execute();
    $uRes = $uStmt->get_result();

    if ($uRes->num_rows > 0) {
        $uRow = $uRes->fetch_assoc();
        $userId = $uRow['id'];
        // Update user name, password and role
        $updUser = $conn->prepare("UPDATE users SET name = ?, password = ?, role = 'doctor' WHERE id = ?");
        $updUser->bind_param("ssi", $d['name'], $hashedPassword, $userId);
        $updUser->execute();
    } else {
        $insUser = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'doctor')");
        $insUser->bind_param("sss", $d['name'], $d['email'], $hashedPassword);
        $insUser->execute();
        $userId = $insUser->insert_id;
    }

    $deptId = $deptMap[$d['dept_code']] ?? null;

    // 2. Ensure doctor record exists
    $dStmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $dStmt->bind_param("i", $userId);
    $dStmt->execute();
    $dRes = $dStmt->get_result();

    if ($dRes->num_rows > 0) {
        $dRow = $dRes->fetch_assoc();
        $docId = $dRow['id'];
        $updDoc = $conn->prepare("UPDATE doctors SET name = ?, doctor_code = ?, department_id = ?, specialization = ?, experience = ?, phone = ?, room_number = ? WHERE id = ?");
        $updDoc->bind_param("ssisissi", $d['name'], $d['code'], $deptId, $d['specialization'], $d['experience'], $d['phone'], $d['room'], $docId);
        $updDoc->execute();
    } else {
        $insDoc = $conn->prepare("INSERT INTO doctors (user_id, doctor_code, name, department_id, specialization, experience, phone, room_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insDoc->bind_param("issisiss", $userId, $d['code'], $d['name'], $deptId, $d['specialization'], $d['experience'], $d['phone'], $d['room']);
        $insDoc->execute();
        $docId = $insDoc->insert_id;
    }

    echo "✓ Doctor: {$d['code']} | {$d['name']} ({$d['specialization']}) | {$d['email']} | Pass: password123\n";
}

// 5. Ensure department head doctor is assigned
foreach ($deptMap as $code => $deptId) {
    $hRes = $conn->query("SELECT id FROM doctors WHERE department_id = $deptId ORDER BY experience DESC LIMIT 1");
    if ($hRes && $hRow = $hRes->fetch_assoc()) {
        $headId = $hRow['id'];
        $conn->query("UPDATE departments SET head_doctor_id = $headId WHERE id = $deptId");
    }
}

// 6. Ensure sample appointments exist for doctors so dashboard is rich
$pRes = $conn->query("SELECT id FROM patients LIMIT 1");
if ($pRes && $pRow = $pRes->fetch_assoc()) {
    $patientId = $pRow['id'];
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    // For first 3 doctors, add a demo appointment if none exists
    $docList = $conn->query("SELECT id FROM doctors LIMIT 5");
    while ($doc = $docList->fetch_assoc()) {
        $did = $doc['id'];
        $checkAppt = $conn->query("SELECT id FROM appointments WHERE doctor_id = $did LIMIT 1");
        if ($checkAppt->num_rows === 0) {
            $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                VALUES ($patientId, $did, '$today', '10:30:00', 'Routine checkup & consultation', 'Pending')");
            $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                VALUES ($patientId, $did, '$tomorrow', '14:00:00', 'Follow-up consultation', 'Approved')");
        }
    }
}

echo "\n🎉 Setup completed successfully!\n";
