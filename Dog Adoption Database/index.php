<?php
require_once __DIR__ . '/db.php';
$search = trim((string) ($_GET['search'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
if ($status !== '' && !valid_status($status)) {
    $status = '';
}

$sql = 'SELECT * FROM dogs WHERE 1 = 1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (name LIKE :search OR breed LIKE :search OR description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $sql .= ' AND status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY created_at DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$dogs = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) AS available,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'Adopted' THEN 1 ELSE 0 END) AS adopted
    FROM dogs
")->fetch();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dog Adoption</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header class="topbar">
        <div>
            <p class="eyebrow">DOG ADOPTION MANAGEMENT</p>
            <h1>A friend for life</h1>
            <p class="subtitle">A little help finding every dog a home.</p>
        </div>
        <a class="button button-primary" href="create.php">+ Add a dog</a>
    </header>

    <?php if ($flash !== ''): ?>
        <div class="notice" role="status"><?= h($flash) ?></div>
    <?php endif; ?>

    <section class="stats" aria-label="Adoption statistics">
        <article class="stat-card">
            <span class="stat-label">Total dogs</span>
            <strong><?= (int) $stats['total'] ?></strong>
        </article>

        <article class="stat-card">
            <span class="stat-label">Available</span>
            <strong><?= (int) ($stats['available'] ?? 0) ?></strong>
        </article>

        <article class="stat-card">
            <span class="stat-label">Pending</span>
            <strong><?= (int) ($stats['pending'] ?? 0) ?></strong>
        </article>

        <article class="stat-card">
            <span class="stat-label">Adopted</span>
            <strong><?= (int) ($stats['adopted'] ?? 0) ?></strong>
        </article>
    </section>

    <section class="panel">
        <form class="filters" action="index.php" method="get">
            <div class="field search-field">
                <label for="search">Search dogs</label>
                <input
                    id="search"
                    name="search"
                    type="search"
                    placeholder="Name, breed, or description"
                    value="<?= h($search) ?>"
                >
            </div>
            <div class="field">
                <label for="status">Adoption status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <option value="Available" <?= $status === 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Adopted" <?= $status === 'Adopted' ? 'selected' : '' ?>>Adopted</option>
                </select>
            </div>
            <button class="button button-primary" type="submit">Search</button>
            <a class="button button-secondary" href="index.php">Clear</a>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <h2>Our dogs</h2>
                <p class="muted">Manage profiles and adoption progress.</p>
            </div>
            <span class="result-count"><?= count($dogs) ?> result(s)</span>
        </div>
        <?php if (!$dogs): ?>
            <div class="empty-state">
                <h3>No dogs found</h3>
                <p>Try another search or add a new dog.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Dog</th>
                        <th>Age</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Update status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dogs as $dog): ?>
                        <tr>
                            <td>
                                <strong class="dog-name"><?= h($dog['name']) ?></strong>
                                <span class="dog-breed"><?= h($dog['breed']) ?></span>
                                <span class="dog-description"><?= h($dog['description']) ?></span>
                            </td>
                            <td><?= (int) $dog['age'] ?> yr<?= (int) $dog['age'] === 1 ? '' : 's' ?></td>
                            <td><?= h($dog['size']) ?></td>
                            <td>
                                <span class="badge badge-<?= h(strtolower($dog['status'])) ?>">
                                    <?= h($dog['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form class="inline-form" action="update-status.php" method="post">
                                    <input type="hidden" name="id" value="<?= (int) $dog['id'] ?>">
                                    <label class="sr-only" for="status-<?= (int) $dog['id'] ?>">
                                        Status for <?= h($dog['name']) ?>
                                    </label>
                                    <select id="status-<?= (int) $dog['id'] ?>" name="status">
                                        <?php foreach (['Available', 'Pending', 'Adopted'] as $option): ?>
                                            <option value="<?= h($option) ?>" <?= $dog['status'] === $option ? 'selected' : '' ?>>
                                                <?= h($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button button-small" type="submit">Save</button>
                                </form>
                            </td>

                            <td>
                                <div class="actions">
                                    <a
                                        class="button button-small button-secondary"
                                        href="edit.php?id=<?= (int) $dog['id'] ?>"
                                    >Edit</a>
                                    <form
                                        action="delete.php"
                                        method="post"
                                        onsubmit="return confirm('Delete this dog permanently?');"
                                    >
                                        <input type="hidden" name="id" value="<?= (int) $dog['id'] ?>">
                                        <button class="button button-small button-danger" type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <footer class="footer">A friend for life · Dog Adoption Management</footer>
</div>
</body>
</html>
