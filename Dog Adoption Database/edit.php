<?php
require_once __DIR__ . '/db.php';
$id = valid_id($_GET['id'] ?? null);
if ($id === null) {
    http_response_code(400);
    exit('Invalid dog ID.');
}

$stmt = $pdo->prepare('SELECT * FROM dogs WHERE id = ?');
$stmt->execute([$id]);
$dog = $stmt->fetch();

if (!$dog) {
    http_response_code(404);
    exit('Dog not found.');
}

$name = $dog['name'];
$breed = $dog['breed'];
$age = (string) $dog['age'];
$size = $dog['size'];
$description = $dog['description'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $breed = trim((string) ($_POST['breed'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $size = (string) ($_POST['size'] ?? '');
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '' || strlen($name) > 80) {
        $errors[] = 'Name is required and must be at most 80 characters long.';
    }
    if ($breed === '' || strlen($breed) > 100) {
        $errors[] = 'Breed is required and must be at most 100 characters long.';
    }
    if ($age === '' || !ctype_digit($age) || (int) $age > 30) {
        $errors[] = 'Age must be a whole number between 0 and 30.';
    }
    if (!valid_size($size)) {
        $errors[] = 'Please select a valid size.';
    }
    if (strlen($description) > 1000) {
        $errors[] = 'Description must be at most 1000 characters long.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare("
            UPDATE dogs
            SET name = :name,
                breed = :breed,
                age = :age,
                size = :size,
                description = :description
            WHERE id = :id
        ");
        $stmt->execute([
            'name' => $name,
            'breed' => $breed,
            'age' => (int) $age,
            'size' => $size,
            'description' => $description,
            'id' => $id,
        ]);
        $_SESSION['flash'] = 'Dog updated successfully!';
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dog | A friend for life</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container form-container">
    <header class="topbar">
        <div>
            <p class="eyebrow">A friend for life</p>
            <h1>Edit <?= h($name) ?></h1>
            <p class="subtitle">Update this dog's adoption profile.</p>
        </div>
        <a class="button button-secondary" href="index.php">← Back to dogs</a>
    </header>
    <?php if ($errors): ?>
        <div class="error-box" role="alert">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <section class="panel">
        <form class="dog-form" action="edit.php?id=<?= $id ?>" method="post">

            <div class="field">
                <label for="name">Name *</label>
                <input id="name" name="name" maxlength="80" required
                       value="<?= h($name) ?>">
            </div>
            <div class="field">
                <label for="breed">Breed *</label>
                <input id="breed" name="breed" maxlength="100" required
                       value="<?= h($breed) ?>">
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="age">Age (years) *</label>
                    <input id="age" name="age" type="number" min="0" max="30"
                           step="1" required value="<?= h($age) ?>">
                </div>
                <div class="field">
                    <label for="size">Size *</label>
                    <select id="size" name="size" required>
                        <?php foreach (['Small', 'Medium', 'Large'] as $option): ?>
                            <option value="<?= h($option) ?>" <?= $size === $option ? 'selected' : '' ?>>
                                <?= h($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"
                          maxlength="1000"><?= h($description) ?></textarea>
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Save changes</button>
                <a class="button button-secondary" href="index.php">Cancel</a>
            </div>
        </form>
    </section>
</div>
</body>
</html>