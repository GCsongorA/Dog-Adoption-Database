<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$id = valid_id($_POST['id'] ?? null);
if ($id === null) {
    http_response_code(400);
    exit('Invalid dog ID.');
}

$stmt = $pdo->prepare('DELETE FROM dogs WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit('Dog not found.');
}
$_SESSION['flash'] = 'Dog deleted successfully!';
redirect('index.php');
