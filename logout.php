<?php
require_once __DIR__ . '/check_session.php';
require_once __DIR__ . '/setRockSession.php';

header('Content-Type: application/json');

// Assume `setRockSession.php` provides a function to clear session
clear_rock_user_session();

echo json_encode(['success' => true, 'message' => 'Logged out.']);
