<?php
declare(strict_types=1);
session_start();

$projectDirectory = __DIR__;
$dbDirectory = $projectDirectory . '/database';

if (!is_dir($dbDirectory)) {
    mkdir($dbDirectory, 0775, true);
}

$dbPath = $dbDirectory . '/dogs.sqlite';

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec("
    CREATE TABLE IF NOT EXISTS dogs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL CHECK(length(name) <= 80),
        breed TEXT NOT NULL CHECK(length(breed) <= 100),
        age INTEGER NOT NULL CHECK(age BETWEEN 0 AND 30),
        size TEXT NOT NULL CHECK(size IN ('Small', 'Medium', 'Large')),
        description TEXT NOT NULL CHECK(length(description) <= 1000),
        status TEXT NOT NULL DEFAULT 'Available'
            CHECK(status IN ('Available', 'Pending', 'Adopted')),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS app_meta (
        meta_key TEXT PRIMARY KEY,
        meta_value TEXT NOT NULL
    )
");

$seedCheck = $pdo->prepare(
    'SELECT meta_value FROM app_meta WHERE meta_key = ?'
);
$seedCheck->execute(['sample_data_seeded']);
$seeded = $seedCheck->fetchColumn();

if ($seeded === false) {
    $pdo->beginTransaction();

    try {
        $seed = $pdo->prepare("
            INSERT INTO dogs (name, breed, age, size, description, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $sampleDogs = [
            ['Buddy', 'Golden Retriever', 3, 'Large',
                'Friendly and energetic. Loves long walks.', 'Available'],
            ['Luna', 'Border Collie', 2, 'Medium',
                'Intelligent and playful. Enjoys learning tricks.', 'Available'],
            ['Max', 'Beagle', 4, 'Medium',
                'Curious and affectionate. Great company.', 'Pending'],
            ['Daisy', 'Chihuahua', 1, 'Small',
                'Small, sweet, and full of personality.', 'Available'],
            ['Rocky', 'German Shepherd', 5, 'Large',
                'Loyal and calm. Needs an active family.', 'Adopted'],
        ];
        foreach ($sampleDogs as $dog) {
            $seed->execute($dog);
        }

        $marker = $pdo->prepare(
            'INSERT INTO app_meta (meta_key, meta_value) VALUES (?, ?)'
        );
        $marker->execute(['sample_data_seeded', '1']);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}


function h(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function valid_id(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        return null;
    }
    return $id;
}

function valid_status(mixed $status): bool
{
    $allowedStatuses = ['Available', 'Pending', 'Adopted'];
    return in_array($status, $allowedStatuses, true);
}

function valid_size(mixed $size): bool
{
    $allowedSizes = ['Small', 'Medium', 'Large'];
    return in_array($size, $allowedSizes, true);
}
