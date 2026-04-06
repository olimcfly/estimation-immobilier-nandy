<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Méthode non autorisée.',
    ], 405);
}

$email = trim((string) ($_POST['email'] ?? ''));
$typeBien = trim((string) ($_POST['type_bien'] ?? ''));
$ville = trim((string) ($_POST['ville'] ?? ''));
$surfaceTranche = trim((string) ($_POST['surface_tranche'] ?? ''));
$budgetEstime = trim((string) ($_POST['budget_estime'] ?? ''));

if ($email === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Adresse email requise.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse([
        'success' => false,
        'message' => 'Adresse email invalide.',
    ], 422);
}

try {
    $db = Database::getConnection();

    $db->exec(
        'CREATE TABLE IF NOT EXISTS rapport_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            email VARCHAR(255) NOT NULL,
            type_bien VARCHAR(80) DEFAULT NULL,
            ville VARCHAR(120) DEFAULT NULL,
            surface_tranche VARCHAR(50) DEFAULT NULL,
            budget_estime VARCHAR(50) DEFAULT NULL,
            UNIQUE KEY uniq_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $insert = $db->prepare(
        'INSERT INTO rapport_requests (email, type_bien, ville, surface_tranche, budget_estime, created_at)
         VALUES (:email, :type_bien, :ville, :surface_tranche, :budget_estime, NOW())
         ON DUPLICATE KEY UPDATE
            type_bien = VALUES(type_bien),
            ville = VALUES(ville),
            surface_tranche = VALUES(surface_tranche),
            budget_estime = VALUES(budget_estime),
            created_at = NOW()'
    );

    $insert->execute([
        'email' => $email,
        'type_bien' => $typeBien,
        'ville' => $ville,
        'surface_tranche' => $surfaceTranche,
        'budget_estime' => $budgetEstime,
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Email enregistré.',
    ]);
} catch (Throwable $exception) {
    jsonResponse([
        'success' => false,
        'message' => 'Impossible d\'enregistrer l\'email pour le moment.',
    ], 500);
}
