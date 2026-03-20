<?php

declare(strict_types=1);

namespace App\Services;

final class EstimationService
{
    private PerplexityService $perplexityService;

    public function __construct(?PerplexityService $perplexityService = null)
    {
        $this->perplexityService = $perplexityService ?? new PerplexityService();
    }

    private const CITY_PRICES = [
        'nandy' => 2800.0,
        'savigny-le-temple' => 2650.0,
        'cesson' => 2900.0,
        'vert-saint-denis' => 2750.0,
        'melun' => 3000.0,
        'moissy-cramayel' => 2550.0,
        'reau' => 2700.0,
        'limoges-fourches' => 2400.0,
        'combs-la-ville' => 2950.0,
        'le mee-sur-seine' => 2500.0,
        'le mée-sur-seine' => 2500.0,
        'seine-port' => 3200.0,
        'saint-fargeau-ponthierry' => 2850.0,
        'lieusaint' => 2750.0,
        'senart' => 2700.0,
        'corbeil-essonnes' => 2600.0,
        'dammarie-les-lys' => 2650.0,
        'la rochette' => 2500.0,
        'vaux-le-penil' => 2850.0,
        'boissise-le-roi' => 3100.0,
        'pringy' => 2900.0,
        'rubelles' => 2800.0,
        'montereau-fault-yonne' => 2100.0,
        'fontainebleau' => 3500.0,
    ];

    public function estimate(string $city, string $propertyType, float $surface, int $rooms): array
    {
        $basePerSqm = $this->resolveBasePrice($city);
        $typeFactor = $this->resolvePropertyTypeFactor($propertyType);
        $surfaceFactor = $this->resolveSurfaceFactor($surface);
        $roomsFactor = $this->resolveRoomsFactor($rooms);

        $perSqmMid = round($basePerSqm * $typeFactor * $surfaceFactor * $roomsFactor, 2);
        $perSqmLow = round($perSqmMid * 0.9, 2);
        $perSqmHigh = round($perSqmMid * 1.1, 2);

        $estimatedLow = round($perSqmLow * $surface, 2);
        $estimatedMid = round($perSqmMid * $surface, 2);
        $estimatedHigh = round($perSqmHigh * $surface, 2);

        return [
            'city' => $city,
            'property_type' => $propertyType,
            'surface' => $surface,
            'rooms' => $rooms,
            'per_sqm_low' => $perSqmLow,
            'per_sqm_mid' => $perSqmMid,
            'per_sqm_high' => $perSqmHigh,
            'estimated_low' => $estimatedLow,
            'estimated_mid' => $estimatedMid,
            'estimated_high' => $estimatedHigh,
        ];
    }

    private function resolveBasePrice(string $city): float
    {
        $cityLower = mb_strtolower(trim($city));

        foreach (self::CITY_PRICES as $key => $price) {
            if (str_contains($cityLower, $key)) {
                return $price;
            }
        }

        // Default: Nandy area average
        return 2800.0;
    }

    private function resolvePropertyTypeFactor(string $propertyType): float
    {
        $typeLower = mb_strtolower($propertyType);

        if (str_contains($typeLower, 'maison') || str_contains($typeLower, 'house') || str_contains($typeLower, 'villa')) {
            return 1.10;
        }

        if (str_contains($typeLower, 'studio')) {
            return 1.08;
        }

        if (str_contains($typeLower, 'terrain')) {
            return 0.35;
        }

        if (str_contains($typeLower, 'loft')) {
            return 1.05;
        }

        return 1.0; // appartement by default
    }

    private function resolveSurfaceFactor(float $surface): float
    {
        if ($surface < 25) {
            return 1.15;
        }

        if ($surface > 150) {
            return 0.90;
        }

        if ($surface > 100) {
            return 0.95;
        }

        return 1.0;
    }

    private function resolveRoomsFactor(int $rooms): float
    {
        if ($rooms <= 1) {
            return 1.05;
        }

        if ($rooms >= 7) {
            return 0.92;
        }

        if ($rooms >= 5) {
            return 0.97;
        }

        return 1.0;
    }
}
