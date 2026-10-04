<?php
// includes/notification-functions.php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/location-functions.php';

function notify($pdo, $userId, $title, $message, $type = 'general', $relatedId = null) {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, title, message, notification_type, related_id)
         VALUES (:uid, :t, :m, :ty, :rid)"
    );
    $stmt->execute([
        ':uid' => $userId, ':t' => $title, ':m' => $message,
        ':ty' => $type,   ':rid' => $relatedId,
    ]);
}

function notify_nearby_role($pdo, $role, $lat, $lng, $title, $message, $type = 'donation', $relatedId = null) {
    $users = users_within_km($pdo, $role, $lat, $lng);
    foreach ($users as $u) {
        notify($pdo, $u['user_id'], $title, $message . " (" . $u['distance_km'] . " KM away)", $type, $relatedId);
    }
    return count($users);
}

function notify_admins($pdo, $title, $message, $type = 'admin', $relatedId = null) {
    $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' AND status = 'active'");
    foreach ($stmt->fetchAll() as $a) {
        notify($pdo, $a['user_id'], $title, $message, $type, $relatedId);
    }
}

function unread_count($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0");
    $stmt->execute([':u' => $userId]);
    return (int)$stmt->fetchColumn();
}

function get_notifications($pdo, $userId, $limit = 30, $unreadOnly = false) {
    $sql = "SELECT * FROM notifications WHERE user_id = :u";
    if ($unreadOnly) $sql .= " AND is_read = 0";
    $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $userId]);
    return $stmt->fetchAll();
}

function mark_notification_read($pdo, $notifId, $userId) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :n AND user_id = :u");
    $stmt->execute([':n' => $notifId, ':u' => $userId]);
}

function mark_all_notifications_read($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :u");
    $stmt->execute([':u' => $userId]);
}
