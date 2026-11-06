<?php

namespace App\Services;

class ShippingCalculator
{
    /**
     * Standard courier rate mapping (per kg, in USD).
     */
    protected const RATES = [
        'JNE' => [
            'name' => 'JNE Express (Reguler)',
            'base_rate' => 5.00,
            'per_kg' => 2.50,
            'etd' => '2-3 hari kerja',
        ],
        'JNT' => [
            'name' => 'J&T Express',
            'base_rate' => 5.50,
            'per_kg' => 2.20,
            'etd' => '1-2 hari kerja',
        ],
        'SICEPAT' => [
            'name' => 'SiCepat BEST',
            'base_rate' => 6.00,
            'per_kg' => 2.80,
            'etd' => '1-2 hari kerja',
        ],
        'POS' => [
            'name' => 'POS Indonesia Kilat Khusus',
            'base_rate' => 4.50,
            'per_kg' => 2.00,
            'etd' => '3-5 hari kerja',
        ],
    ];

    /**
     * Calculate shipping fee based on weight and courier.
     */
    public static function calculate(string $destinationPostal, int $weightGram, string $courier = 'JNE'): array
    {
        $courierKey = strtoupper($courier);
        $courierData = self::RATES[$courierKey] ?? self::RATES['JNE'];

        // Weight rounded up to nearest 1000g (1 kg minimum)
        $weightKg = max(1, (int) ceil($weightGram / 1000));

        $baseFee = $courierData['base_rate'];
        $additionalWeightFee = max(0, $weightKg - 1) * $courierData['per_kg'];

        // Slight regional variation based on postal code prefix
        $regionalMultiplier = 1.0;
        if (str_starts_with($destinationPostal, '1')) {
            // Jabodetabek area
            $regionalMultiplier = 1.0;
        } elseif (str_starts_with($destinationPostal, '2') || str_starts_with($destinationPostal, '3')) {
            // Jawa Barat / Banten
            $regionalMultiplier = 1.1;
        } else {
            // Luar pulau / antar provinsi
            $regionalMultiplier = 1.25;
        }

        $totalFee = round(($baseFee + $additionalWeightFee) * $regionalMultiplier, 2);

        return [
            'courier' => $courierKey,
            'courier_name' => $courierData['name'],
            'weight_gram' => $weightGram,
            'weight_kg' => $weightKg,
            'fee' => $totalFee,
            'etd' => $courierData['etd'],
        ];
    }

    /**
     * List all available courier options with their calculated rates.
     */
    public static function availableOptions(string $destinationPostal, int $weightGram): array
    {
        $options = [];
        foreach (array_keys(self::RATES) as $courier) {
            $options[] = self::calculate($destinationPostal, $weightGram, $courier);
        }

        return $options;
    }
}
