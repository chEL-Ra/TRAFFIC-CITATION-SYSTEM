<?php
require 'db.php'; 

$officers = [
    'benjamin.taylor'   => 'pass123', // Fixed name
    'isabella.anderson' => 'pass123'  // Fixed name
];

try {
    foreach ($officers as $username => $plainPassword) {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE officers SET pass = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        echo "Updated: $username <br>";
    }
    echo "<b>All 10 officers are now ready!</b>";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>