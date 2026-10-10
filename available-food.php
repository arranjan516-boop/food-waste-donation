<?php
// available-food.php
$pageTitle = 'Available Food';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/location-functions.php';

// Guest browsing: no 15 KM filter (they don't have a location yet)
// Filters from query string
$cat    = get('category');
$type   = get('type');
$city   = get('city');
$urg    = get('urgency');
$search = get('q');

$sql = "SELECT d.*, u.name AS donor_name, u.area AS donor_area, u.city AS donor_city
        FROM food_donations d
        JOIN users u ON u.user_id = d.donor_id
        WHERE d.status = 'available'
          AND (d.best_before IS NULL OR d.best_before > NOW())";
$params = [];

if ($cat)    { $sql .= " AND d.food_category = :cat";  $params[':cat'] = $cat; }
if ($type)   { $sql .= " AND d.food_type = :type";     $params[':type'] = $type; }
if ($city)   { $sql .= " AND (d.city LIKE :city1 OR d.area LIKE :city2)";
               $params[':city1'] = "%$city%";
               $params[':city2'] = "%$city%"; }
if ($urg)    { $sql .= " AND d.urgency = :urg";        $params[':urg'] = $urg; }
if ($search) { $sql .= " AND (d.food_name LIKE :q1 OR d.description LIKE :q2)";
               $params[':q1'] = "%$search%";
               $params[':q2'] = "%$search%"; }

$sql .= " ORDER BY d.created_at DESC LIMIT 60";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll();

// Distinct cities for filter dropdown
$cities = $pdo->query("SELECT DISTINCT city FROM food_donations WHERE city IS NOT NULL AND city <> '' ORDER BY city")->fetchAll();
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Available Food</h1>
        <p class="section-sub">
            <?= is_logged_in() ? 'Donations within 15 KM of your location are highlighted after login.' : 'Login to see donations within 15 KM of you.' ?>
        </p>

        <!-- Filters -->
        <form method="get" class="filter-bar">
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="🔍 Search food, description..." class="form-control">
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach (['Rice','Meals','Curry','Bread','Fruits','Vegetables','Bakery','Snacks','Other'] as $c): ?>
                    <option value="<?= $c ?>" <?= $cat===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="Vegetarian"     <?= $type==='Vegetarian'?'selected':'' ?>>Vegetarian</option>
                <option value="Non-Vegetarian" <?= $type==='Non-Vegetarian'?'selected':'' ?>>Non-Vegetarian</option>
                <option value="Vegan"          <?= $type==='Vegan'?'selected':'' ?>>Vegan</option>
            </select>
            <select name="city" class="form-control">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?= sanitize($c['city']) ?>" <?= $city===$c['city']?'selected':'' ?>><?= sanitize($c['city']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="urgency" class="form-control">
                <option value="">Any Urgency</option>
                <option value="normal"      <?= $urg==='normal'?'selected':'' ?>>Normal</option>
                <option value="urgent"      <?= $urg==='urgent'?'selected':'' ?>>Urgent</option>
                <option value="very_urgent" <?= $urg==='very_urgent'?'selected':'' ?>>Very Urgent</option>
            </select>
            <button class="btn btn-primary">Filter</button>
            <a href="<?= BASE_URL ?>available-food.php" class="btn btn-outline">Reset</a>
        </form>

        <?php if (!$donations): ?>
            <div class="empty-state">
                <div class="empty-icon">🍽️</div>
                <h3>No donations found</h3>
                <p>Try adjusting your filters or check back later.</p>
            </div>
        <?php else: ?>
            <div class="food-grid">
                <?php foreach ($donations as $d): ?>
                    <?php
                        $photo = food_photo_url($d['food_photo']);
                        $urgMap = [
                            'urgent'      => ['badge-red',    'Urgent'],
                            'very_urgent' => ['badge-red',    'Very Urgent'],
                            'normal'      => ['badge-yellow', 'Normal'],
                        ];
                        [$uCls, $uTxt] = $urgMap[$d['urgency']] ?? ['badge-gray', $d['urgency']];
                        $bb = $d['best_before'] ? date('d M, h:i A', strtotime($d['best_before'])) : '—';
                    ?>
                    <div class="food-card">
                        <img src="<?= $photo ?>" alt="<?= sanitize($d['food_name']) ?>">
                        <div class="food-card-body">
                            <div class="flex-between">
                                <div class="food-card-title"><?= sanitize($d['food_name']) ?></div>
                                <span class="badge <?= $uCls ?>"><?= $uTxt ?></span>
                            </div>
                            <div class="food-card-meta">
                                <span>🍽️ <?= (int)$d['people_served'] ?> meals</span>
                                <span>🥗 <?= sanitize($d['food_type'] ?: 'Veg') ?></span>
                            </div>
                            <div class="food-card-meta">
                                <span>📍 <?= sanitize($d['area'] ?: $d['city'] ?: 'Nearby') ?></span>
                                <span>⏰ <?= $bb ?></span>
                            </div>
                            <div class="food-card-meta text-muted">
                                By <?= sanitize($d['donor_name']) ?>
                            </div>
                            <div class="food-card-actions">
                                <a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-sm">Login to Request</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
