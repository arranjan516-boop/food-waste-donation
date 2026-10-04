<?php
// includes/notification-functions.php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/location-functions.php';

function notify($pdo, $userId, $title, $message, $type = 'general', $relatedId = null) {
    $sql = "INSERT INTO notifications (user_id, title, message, notification_type, related_id)
            VALUES (:uid, :t, :m, :ty, :rid)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $userId,
        ':t'   => $title,
        ':m'   => $message,
        ':ty'  => $type,
        ':rid' => $relatedId,
    ]);
}

/**
 * Notify nearby users of a given role about a new donation.
 */
function notify_nearby_role($pdo, $role, $lat, $lng, $title, $message, $type = 'donation', $relatedId = null) {
    $users = users_within_km($pdo, $role, $lat, $lng);
    foreach ($users as $u) {
        $msg = $message . " (" . $u['distance_km'] . " KM away)";
        notify($pdo, $u['user_id'], $title, $msg, $type, $relatedId);
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
