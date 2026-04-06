<?php

declare(strict_types=1);

/**
 * Service centralisant la logique d'estimation immobilière.
 *
 * Stratégie:
 * 1) Tenter Perplexity (si clé disponible)
 * 2) Tenter Mammouth AI (si clé + endpoint disponibles)
 * 3) Repli local déterministe (toujours disponible)
 */
final class EstimationService
{
    private const PERPLEXITY_ENDPOINT = 'https://api.perplexity.ai/chat/completions';
    private const MAMMOUTH_ENDPOINT_ENV = 'AI_MAMMOUTH_ENDPOINT';

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function estimate(array $payload): array
    {
        $normalized = $this->normalizePayload($payload);

        $providers = [
            'perplexity' => function (array $data): ?array {
                $key = $this->getPerplexityKey();
                if ($key === '') {
                    return null;
                }

                return $this->callPerplexity($data, $key);
            },
            'mammouth' => function (array $data): ?array {
                $key = $this->getMammouthKey();
                $endpoint = getenv(self::MAMMOUTH_ENDPOINT_ENV) ?: '';
                if ($key === '' || $endpoint === '') {
                    return null;
                }

                return $this->callMammouth($data, $key, $endpoint);
            },
        ];

        foreach ($providers as $provider => $resolver) {
            try {
                $result = $resolver($normalized);
                if (is_array($result)) {
                    $result['provider'] = $provider;
                    return $result;
                }
            } catch (Throwable $exception) {
                $this->log('provider_error', [
                    'provider' => $provider,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $fallback = $this->fallbackEstimate($normalized);
        $fallback['provider'] = 'fallback';

        return $fallback;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{type_bien:string,ville:string,surface_m2:int,pieces:int,etat:string}
     */
    private function normalizePayload(array $payload): array
    {
        $typeBien = trim((string) ($payload['type_bien'] ?? ''));
        $ville = trim((string) ($payload['ville'] ?? ''));
        $surfaceM2 = (int) ($payload['surface_m2'] ?? 0);
        $pieces = (int) ($payload['pieces'] ?? 0);
        $etat = trim((string) ($payload['etat'] ?? ''));

        if ($typeBien === '' || $ville === '' || $surfaceM2 <= 0) {
            throw new InvalidArgumentException('Données insuffisantes pour produire une estimation.');
        }

        if ($pieces <= 0) {
            $pieces = 3;
        }

        if ($etat === '') {
            $etat = 'bon';
        }

        return [
            'type_bien' => $typeBien,
            'ville' => $ville,
            'surface_m2' => max(12, min($surfaceM2, 600)),
            'pieces' => max(1, min($pieces, 15)),
            'etat' => $etat,
        ];
    }

    private function getPerplexityKey(): string
    {
        if (defined('AI_PERPLEXITY_KEY') && AI_PERPLEXITY_KEY !== '') {
            return (string) AI_PERPLEXITY_KEY;
        }

        return (string) (getenv('AI_PERPLEXITY_KEY') ?: '');
    }

    private function getMammouthKey(): string
    {
        return (string) (getenv('AI_MAMMOUTH_KEY') ?: '');
    }

    /**
     * @param array{type_bien:string,ville:string,surface_m2:int,pieces:int,etat:string} $data
     * @return array<string,mixed>|null
     */
    private function callPerplexity(array $data, string $apiKey): ?array
    {
        $prompt = $this->buildPrompt($data);

        $response = $this->httpPost(
            self::PERPLEXITY_ENDPOINT,
            [
                'model' => 'sonar',
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un expert de l\'estimation immobilière en France. Réponds strictement en JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ],
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ]
        );

        $decoded = json_decode($response, true);
        $content = (string) ($decoded['choices'][0]['message']['content'] ?? '');

        return $this->extractEstimationFromText($content);
    }

    /**
     * @param array{type_bien:string,ville:string,surface_m2:int,pieces:int,etat:string} $data
     * @return array<string,mixed>|null
     */
    private function callMammouth(array $data, string $apiKey, string $endpoint): ?array
    {
        // Mammouth n'ayant pas de standard unique public ici, endpoint personnalisable via variable d'environnement.
        $response = $this->httpPost(
            $endpoint,
            [
                'input' => $this->buildPrompt($data),
                'format' => 'json',
            ],
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ]
        );

        $decoded = json_decode($response, true);

        if (is_array($decoded) && isset($decoded['estimation_low'], $decoded['estimation_high'])) {
            return [
                'estimation_basse' => (int) $decoded['estimation_low'],
                'estimation_haute' => (int) $decoded['estimation_high'],
                'contexte_marche' => (string) ($decoded['market_context'] ?? 'Le marché local présente des écarts selon l’état du bien et la micro-localisation.'),
                'explication' => (string) ($decoded['short_explanation'] ?? 'Estimation produite depuis les caractéristiques déclarées.'),
            ];
        }

        return null;
    }

    /**
     * @param array{type_bien:string,ville:string,surface_m2:int,pieces:int,etat:string} $data
     */
    private function buildPrompt(array $data): string
    {
        return sprintf(
            'Retourne uniquement un JSON avec les clés estimation_basse, estimation_haute, contexte_marche, explication. Données: type=%s, ville=%s, surface=%dm2, pieces=%d, etat=%s. Fourchette réaliste en euros pour la France.',
            $data['type_bien'],
            $data['ville'],
            $data['surface_m2'],
            $data['pieces'],
            $data['etat']
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    private function extractEstimationFromText(string $text): ?array
    {
        if ($text === '') {
            return null;
        }

        if (preg_match('/\{[\s\S]*\}/', $text, $matches) !== 1) {
            return null;
        }

        $json = json_decode($matches[0], true);
        if (!is_array($json)) {
            return null;
        }

        $low = (int) ($json['estimation_basse'] ?? 0);
        $high = (int) ($json['estimation_haute'] ?? 0);

        if ($low <= 0 || $high <= 0 || $high < $low) {
            return null;
        }

        return [
            'estimation_basse' => $low,
            'estimation_haute' => $high,
            'contexte_marche' => (string) ($json['contexte_marche'] ?? 'Le marché local est dynamique mais sensible à la qualité énergétique et à l’emplacement précis.'),
            'explication' => (string) ($json['explication'] ?? 'Fourchette calculée à partir des caractéristiques fournies et des tendances récentes.'),
        ];
    }

    /**
     * @param array{type_bien:string,ville:string,surface_m2:int,pieces:int,etat:string} $data
     * @return array<string,mixed>
     */
    private function fallbackEstimate(array $data): array
    {
        $baseM2 = [
            'Appartement' => 3200,
            'Maison' => 3600,
            'Terrain' => 260,
            'Local commercial' => 2900,
        ];

        $cityMod = [
            'Nandy' => 1.00,
            'Savigny-le-Temple' => 1.04,
            'Cesson' => 1.09,
            'Vert-Saint-Denis' => 1.06,
            'Moissy-Cramayel' => 0.97,
            'Lieusaint' => 1.12,
        ];

        $stateMod = [
            'à rénover' => 0.84,
            'bon' => 1.00,
            'excellent' => 1.12,
            'neuf' => 1.18,
        ];

        $typeValue = $baseM2[$data['type_bien']] ?? 3300;
        $cityValue = $cityMod[$data['ville']] ?? 1.0;
        $stateValue = $stateMod[mb_strtolower($data['etat'])] ?? 1.0;
        $roomBoost = 1 + (($data['pieces'] - 3) * 0.015);

        $central = $typeValue * $cityValue * $stateValue * $roomBoost * $data['surface_m2'];

        return [
            'estimation_basse' => (int) round($central * 0.92),
            'estimation_haute' => (int) round($central * 1.08),
            'contexte_marche' => 'Le marché local reste sélectif: les biens bien positionnés en prix se vendent plus vite, surtout proches des transports et services.',
            'explication' => 'Fourchette indicative calculée selon le type de bien, la surface, l’état déclaré et un coefficient de tension locale.',
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function log(string $event, array $payload): void
    {
        @file_put_contents(
            __DIR__ . '/../logs/estimation-service.log',
            date('Y-m-d H:i:s') . ' | ' . $event . ' | ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * @param array<string,mixed> $body
     * @param list<string> $headers
     */
    private function httpPost(string $url, array $body, array $headers): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $response === '' || $code >= 400) {
            throw new RuntimeException('HTTP error ' . $code . ' ' . $error);
        }

        return (string) $response;
    }
}
