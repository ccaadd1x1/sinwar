<?php
// auth1.php
session_start();

header('Content-Type: application/json');
error_reporting(0); // Hide notices/warnings in response

try {
    require 'db_config.php'; // Ensure this file exists and works

    // Read raw JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username or password missing.']);
        exit;
    }

    // Lookup user
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid username.']);
        exit;
    }

    // Password check — uses password_hash() hashes
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit;
    }

    // Set session and return success
    $_SESSION['auth'] = true;
    $_SESSION['user'] = $username;

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    // Catch everything (missing DB, bad query, etc.)
    echo json_encode([
        'success' => false,
        'message' => 'Server error during login.',
        'error' => $e->getMessage() // Show for debug; remove in production
    ]);
    exit;
}
