<?php

namespace App\Services;

use App\Models\FoodDonation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FoodMetricsService
{
    /**
     * Standardized humanitarian food conversion factors (UN WFP / FSSAI benchmarks):
     * - 1 serving / plate = 1 actual meal
     * - 1 kg cooked food ≈ 2.5 meals (standard 400g adult meal portion)
     * - 1 packet / box ≈ 1 meal
     * - 1 litre ≈ 2.5 meals
     * - 1 gram ≈ 0.0025 meals (1 kg / 1000 * 2.5)
     */
    public const KG_TO_MEALS = 2.5;
    public const LITRE_TO_MEALS = 2.5;
    public const PACKET_TO_MEALS = 1.0;
    public const GRAMS_TO_MEALS = 0.0025;

    /**
     * Scope query to strictly verified and completed donations.
     * Excludes pending, rejected, cancelled, expired, or unverified donations.
     */
    public static function completedDonationsScope(?Builder $query = null): Builder
    {
        $q = $query ?? FoodDonation::query();
        return $q->whereIn('status', ['completed', 'picked']);
    }

    /**
     * Calculate meal count estimate for a single food donation based on standard humanitarian benchmarks.
     */
    public function calculateDonationMeals(?FoodDonation $donation): int
    {
        if (!$donation) {
            return 1;
        }

        $qty = (float) $donation->quantity;
        if ($qty <= 0) {
            return 1;
        }

        $unit = strtolower(trim($donation->unit ?? ''));

        if (in_array($unit, ['servings', 'serving', 'plates', 'plate', 'meals', 'meal'])) {
            return max(1, (int) round($qty));
        }

        if (in_array($unit, ['kg', 'kilograms', 'kilogram', 'kgs'])) {
            return max(1, (int) round($qty * self::KG_TO_MEALS));
        }

        if (in_array($unit, ['boxes', 'box', 'packets', 'packet', 'packs'])) {
            return max(1, (int) round($qty * self::PACKET_TO_MEALS));
        }

        if (in_array($unit, ['litres', 'litre', 'l', 'ltr'])) {
            return max(1, (int) round($qty * self::LITRE_TO_MEALS));
        }

        if (in_array($unit, ['grams', 'g', 'gram', 'gms'])) {
            return max(1, (int) round(($qty / 1000) * self::KG_TO_MEALS));
        }

        // For other or unmeasured units, return 1 as a conservative minimum estimate
        return max(1, (int) round($qty));
    }

    /**
     * Calculate comprehensive, unit-separated metrics for a given query scope.
     */
    public function calculateMetrics(?Builder $donationQuery = null): array
    {
        $donations = self::completedDonationsScope($donationQuery)
            ->select('quantity', 'unit', 'status')
            ->get();

        $byUnit = [
            'servings' => 0.0,
            'kg' => 0.0,
            'boxes' => 0.0,
            'packets' => 0.0,
            'plates' => 0.0,
            'litres' => 0.0,
            'grams' => 0.0,
            'other' => 0.0,
        ];

        $actualServings = 0.0;
        $estimatedMeals = 0.0;
        $completedCount = 0;
        $pickedCount = 0;

        foreach ($donations as $item) {
            if ($item->status === 'completed') {
                $completedCount++;
            } elseif ($item->status === 'picked') {
                $pickedCount++;
            }

            $qty = (float) $item->quantity;
            $unit = strtolower(trim($item->unit ?? ''));

            if (in_array($unit, ['servings', 'serving'])) {
                $byUnit['servings'] += $qty;
                $actualServings += $qty;
                $estimatedMeals += $qty;
            } elseif (in_array($unit, ['plates', 'plate', 'meals', 'meal'])) {
                $byUnit['plates'] += $qty;
                $actualServings += $qty;
                $estimatedMeals += $qty;
            } elseif (in_array($unit, ['kg', 'kilograms', 'kilogram', 'kgs'])) {
                $byUnit['kg'] += $qty;
                $estimatedMeals += round($qty * self::KG_TO_MEALS, 1);
            } elseif (in_array($unit, ['boxes', 'box'])) {
                $byUnit['boxes'] += $qty;
                $estimatedMeals += round($qty * self::PACKET_TO_MEALS, 1);
            } elseif (in_array($unit, ['packets', 'packet', 'packs'])) {
                $byUnit['packets'] += $qty;
                $estimatedMeals += round($qty * self::PACKET_TO_MEALS, 1);
            } elseif (in_array($unit, ['litres', 'litre', 'l', 'ltr'])) {
                $byUnit['litres'] += $qty;
                $estimatedMeals += round($qty * self::LITRE_TO_MEALS, 1);
            } elseif (in_array($unit, ['grams', 'g', 'gram', 'gms'])) {
                $byUnit['grams'] += $qty;
                $estimatedMeals += round(($qty / 1000) * self::KG_TO_MEALS, 1);
            } else {
                $byUnit['other'] += $qty;
                // Exclude unknown units from inflating meal counts
            }
        }

        // Total solid mass diverted in kilograms (kg + grams/1000)
        $totalWeightKg = round($byUnit['kg'] + ($byUnit['grams'] / 1000), 2);

        // CO2 emissions prevented:
        // EPA WARM / FAO benchmark: 1 kg solid food diverted from landfill avoids ~2.5 kg CO2e;
        // Rescued prepared meal portion saves ~1.25 kg CO2e
        $co2Kg = round(
            ($totalWeightKg * 2.5) +
            ($actualServings * 1.25) +
            (($byUnit['boxes'] + $byUnit['packets']) * 1.25) +
            ($byUnit['litres'] * 1.25),
            1
        );

        return [
            'total_completed_count' => $donations->count(),
            'completed_count' => $completedCount,
            'picked_count' => $pickedCount,
            'by_unit' => $byUnit,
            'actual_servings' => round($actualServings, 0),
            'weight_kg' => $totalWeightKg,
            'volume_litres' => round($byUnit['litres'], 1),
            'boxes_and_packets' => round($byUnit['boxes'] + $byUnit['packets'], 0),
            'estimated_meals' => (int) round($estimatedMeals),
            'total_meals_rescued' => (int) round($estimatedMeals),
            'co2_saved_kg' => $co2Kg,
        ];
    }
}
