<?php
session_start();
$_SESSION['user_id'] = (int)($_GET['user_id'] ?? 15);
$_SESSION['user_type'] = $_GET['user_type'] ?? 'mentor';
$_SESSION['mentorship_status'] = 'approved';
header('Content-Type: application/json');
echo json_encode(['success' => true, 'session_id' => session_id(), 'user_id' => $_SESSION['user_id']]);
