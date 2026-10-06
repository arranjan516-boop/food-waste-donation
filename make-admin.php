<?php
// TEMPORARY — delete after use
require_once __DIR__ . '/config/database.php';

$email    = 'admin@foodshare.local';
$password = 'password123';

$hash = password_hash($password, PASSWORD_DEFAULT);

// Delete any existing admin row with this email, then insert fresh
$pdo->prepare("DELETE FROM users WHERE email = :e")->execute([':e' => $email]);

$stmt = $pdo->prepare("
    INSERT INTO users (name, email, password, phone, role, city, area, pincode, status)
    VALUES (:n, :e, :p, :ph, 'admin', 'Tumkur', 'SIT', '572106', 'active')
");
$stmt->execute([
    ':n'  => 'Administrator',
    ':e'  => $email,
    ':p'  => $hash,
    ':ph' => '9000000000',
]);

echo "<h2 style='color:green'>✅ Admin created successfully!</h2>";
echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
echo "<p><strong>Hash stored:</strong> " . htmlspecialchars($hash) . "</p>";
echo "<p><a href='login.php'>→ Go to Login</a></p>";
echo "<p style='color:red'><strong>⚠️ Delete this file (make-admin.php) now!</strong></p>";
