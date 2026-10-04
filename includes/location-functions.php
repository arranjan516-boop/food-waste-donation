<?php
// includes/location-functions.php
require_once __DIR__ . '/functions.php';

/**
 * Returns users of a given role within $radius KM of a point.
 * Uses SQL bounding-box prefilter, then exact Haversine in PHP.
 */
function users_within_km($pdo, $role, $lat, $lng, $radius = MATCH_RADIUS_KM, $excludeUserId = null) {
    if ($lat === null || $lng === null) return [];

    // Rough bounding box (1 deg lat ≈ 111 km)
    $latDelta = $radius / 111.0;
    $lngDelta = $radius / (111.0 * max(cos(deg2rad($lat)), 0.01));

    $sql = "SELECT * FROM users
            WHERE role = :role
              AND status = 'active'
              AND latitude  IS NOT NULL
              AND longitude IS NOT NULL
              AND latitude  BETWEEN :lat1 AND :lat2
              AND longitude BETWEEN :lng1 AND :lng2";
    $params = [
        ':role' => $role,
        ':lat1' => $lat - $latDelta,
        ':lat2' => $lat + $latDelta,
        ':lng1' => $lng - $lngDelta,
        ':lng2' => $lng + $lngDelta,
    ];
    if ($excludeUserId) {
        $sql .= " AND user_id <> :uid";
        $params[':uid'] = $excludeUserId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Exact filter
    $result = [];
    foreach ($rows as $u) {
        $d = haversine_km($lat, $lng, $u['latitude'], $u['longitude']);
        if ($d !== null && $d <= $radius) {
            $u['distance_km'] = round($d, 2);
            $result[] = $u;
        }
    }
    usort($result, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
    return $result;
}

/**
 * Filter an array of donations/requests to those within radius of a user.
 * Expects each item to have latitude/longitude keys.
 */
function filter_within_km(array $items, $userLat, $userLng, $radius = MATCH_RADIUS_KM) {
    if ($userLat === null || $userLng === null) return [];
    $out = [];
    foreach ($items as $it) {
        $d = haversine_km($userLat, $userLng, $it['latitude'] ?? null, $it['longitude'] ?? null);
        if ($d !== null && $d <= $radius) {
            $it['distance_km'] = round($d, 2);
            $out[] = $it;
        }
    }
    return $out;
}

/**
 * Format distance for display:  "📍 4.2 KM away"
 */
function distance_label($km) {
    if ($km === null) return '';
    return "📍 " . number_format($km, 1) . " KM away";
}
