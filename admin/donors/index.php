<?php
// admin/donors/index.php
$pageTitle = 'Donors';
require_once __DIR__ . '/../../includes/dashboard-header.php';

if (current_role() !== 'admin') { redirect(BASE_URL . 'index.php'); }

$ROLE = 'donor'; // ← change per file

$rows = $pdo->prepare("SELECT * FROM users WHERE role = :r ORDER BY created_at DESC LIMIT 200");
$rows->execute([':r' => $ROLE]);
$rows = $rows->fetchAll();
?>

<div class="table-card">
    <div class="table-card-header"><h3><?= ucfirst($ROLE) ?>s (<?= count($rows) ?>)</h3></div>
    <?php if (!$rows): ?>
        <div style="padding:40px;text-align:center"><p class="text-muted">None yet.</p></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Photo</th><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $u): ?>
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
                    <td><?= sanitize($u['phone'] ?: '—') ?></td>
                    <td><?= sanitize($u['area'] ?: $u['city'] ?: '—') ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td><a href="<?= BASE_URL ?>admin/users/view.php?id=<?= (int)$u['user_id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>.avatar-mini{width:44px;height:44px;border-radius:8px;background:var(--green);color:#fff;
    display:flex;align-items:center;justify-content:center;font-weight:600;}</style>

<?php require_once __DIR__ . '/../../includes/dashboard-footer.php'; ?>
