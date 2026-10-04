<?php
// api/notifications.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['count' => 0]);
    exit;
}

$action = $_GET['action'] ?? 'count';
$uid    = current_user_id();

if ($action === 'count') {
    echo json_encode(['count' => unread_count($pdo, $uid)]);
    exit;
}

if ($action === 'list') {
    echo json_encode(get_notifications($pdo, $uid, 20));
    exit;
}

if ($action === 'read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    mark_notification_read($pdo, $id, $uid);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'read_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    mark_all_notifications_read($pdo, $uid);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'invalid action']);
