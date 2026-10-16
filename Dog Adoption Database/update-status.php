<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}
$id = valid_id($_POST['id'] ?? null);
$status = $_POST['status'] ?? null;
if ($id === null || !valid_status($status)) {
    http_response_code(400);
    exit('Invalid dog ID or status.');
}
$stmt = $pdo->prepare('UPDATE dogs SET status = ? WHERE id = ?');
$stmt->execute([$status, $id]);

if ($stmt->rowCount() === 0) {
    $check = $pdo->prepare('SELECT id FROM dogs WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        http_response_code(404);
        exit('Dog not found.');
    }
}

$_SESSION['flash'] = 'Adoption status updated successfully!';
redirect('index.php');