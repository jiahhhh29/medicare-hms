<?php
require 'includes/db.php';
$p = password_hash('password123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '$p' WHERE role = 'lab'");
echo "Done";
?>
