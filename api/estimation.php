<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/estimation-service.php';

/**
 * @param array<string, mixed> $payload
 */
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

$rawInput = file_get_contents('php://input') ?: '';
$payload = $_POST;

if ($rawInput !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

try {
    $service = new EstimationService();
    $estimation = $service->estimate($payload);

    jsonResponse([
        'success' => true,
        'estimation_basse' => (int) $estimation['estimation_basse'],
        'estimation_haute' => (int) $estimation['estimation_haute'],
        'contexte_marche' => (string) $estimation['contexte_marche'],
        'explication' => (string) $estimation['explication'],
        'provider' => (string) ($estimation['provider'] ?? 'fallback'),
    ]);
} catch (InvalidArgumentException $exception) {
    jsonResponse([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
} catch (Throwable $exception) {
    jsonResponse([
        'success' => false,
        'message' => 'Impossible de générer l’estimation pour le moment.',
    ], 500);
}
