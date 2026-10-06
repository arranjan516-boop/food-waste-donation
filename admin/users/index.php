<?php
// admin/users/index.php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$role   = get('role');
$search = get('q');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($role)   { $sql .= " AND role = :r"; $params[':r'] = $role; }
if ($search) { $sql .= " AND (name LIKE :q OR email LIKE :q OR phone LIKE :q)"; $params[':q'] = "%$search%"; }
$sql .= " ORDER BY created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<form method="get" class="filter-bar" style="grid-template-columns: 2fr 1fr auto auto">
    <input type="text" name="q" value="<?= sanitize($search) ?>" class="form-control" placeholder="🔍 Search name, email, phone...">
    <select name="role" class="form-control">
        <option value="">All Roles</option>
        <?php foreach (['donor','recipient','collector','ngo','admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $role===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
    <a href="?" class="btn btn-outline">Reset</a>
</form>

<div class="table-card">
    <div class="table-card-header"><h3>Users (<?= count($users) ?>)</h3></div>
    <?php if (!$users): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">No users found.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Photo</th><th>Name</th><th>Email</th><th>Role</th><th>Location</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <?php if ($u['profile_photo']): ?>
                            <img class="thumb" src="<?= PROFILE_UPLOAD_URL . rawurlencode($u['profile_photo']) ?>" alt="">
                        <?php else: ?>
                            <div class="avatar-mini"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($u['name']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><span class="badge badge-blue"><?= sanitize(ucfirst($u['role'])) ?></span></td>
                    <td><?= sanitize($u['area'] ?: $u['city'] ?: '—') ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td><a href="view.php?id=<?= (int)$u['user_id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.avatar-mini { width:44px;height:44px;border-radius:8px;background:var(--green);color:#fff;
    display:flex;align-items:center;justify-content:center;font-weight:600; }
</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
