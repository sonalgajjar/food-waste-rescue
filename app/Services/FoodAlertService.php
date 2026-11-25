<?php

namespace App\Services;

use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FoodAlertService
{
    public const DEFAULT_ALERT_RADIUS_KM = 15.0;

    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Identify all active NGOs located within the given radius (or same city) of the donation.
     *
     * @return Collection<int, array{ngo: NGO, distance: ?float}>
     */
    public function getNearbyNgos(FoodDonation $donation, ?float $radiusKm = null): Collection
    {
        $radius = $radiusKm ?? (float) config('foodrescue.alert_radius_km', self::DEFAULT_ALERT_RADIUS_KM);

        $donation->loadMissing('restaurant');

        $activeNgos = NGO::where('status', 'active')
            ->whereHas('user', function ($q) {
                $q->where('status', 'active');
            })
            ->with('user')
            ->get();

        $nearby = collect();

        foreach ($activeNgos as $ngo) {
            $distance = null;
            $isNearby = false;

            if (
                $donation->latitude !== null &&
                $donation->longitude !== null &&
                $ngo->latitude !== null &&
                $ngo->longitude !== null
            ) {
                $distance = $donation->calculateDistanceTo((float) $ngo->latitude, (float) $ngo->longitude);
                if ($distance !== null && $distance <= $radius) {
                    $isNearby = true;
                }
            } elseif (!empty($ngo->city) && !empty($donation->restaurant?->city)) {
                if (strcasecmp(trim($ngo->city), trim($donation->restaurant->city)) === 0) {
                    $isNearby = true;
                }
            }

            if ($isNearby) {
                $nearby->push([
                    'ngo' => $ngo,
                    'distance' => $distance,
                ]);
            }
        }

        return $nearby;
    }

    /**
     * Dispatch in-app notifications and WhatsApp alerts to nearby active NGOs for newly posted food.
     *
     * @return int Total number of NGOs alerted
     */
    public function notifyNearbyNgos(FoodDonation $donation, ?float $radiusKm = null): int
    {
        $nearbyList = $this->getNearbyNgos($donation, $radiusKm);
        $alertedCount = 0;

        $restaurantName = $donation->restaurant?->restaurant_name ?? 'A nearby restaurant';
        $expiryFormatted = $donation->expiry_time ? $donation->expiry_time->format('M d, h:i A') : 'soon';

        foreach ($nearbyList as $item) {
            /** @var NGO $ngo */
            $ngo = $item['ngo'];
            $distance = $item['distance'];

            if (!$ngo->user_id) {
                continue;
            }

            // Avoid duplicate alerts for the same donation and NGO user
            $alreadyAlerted = Notification::where('user_id', $ngo->user_id)
                ->where('food_donation_id', $donation->id)
                ->where('type', 'food_alert')
                ->exists();

            if ($alreadyAlerted) {
                continue;
            }

            $distStr = $distance !== null
                ? " (~{$distance} km away)"
                : (!empty($donation->restaurant?->city) ? " in {$donation->restaurant->city}" : '');

            $targetUrl = route('ngo.donations.show', $donation->id);

            // 1. In-app Notification
            Notification::create([
                'user_id' => $ngo->user_id,
                'type' => 'food_alert',
                'title' => 'Nearby Surplus Food Alert',
                'message' => "Fresh surplus food posted: \"{$donation->food_name}\" ({$donation->quantity} {$donation->unit}) from {$restaurantName}{$distStr}. Claim pickup before {$expiryFormatted}!",
                'link' => $targetUrl,
                'food_donation_id' => $donation->id,
                'is_read' => false,
            ]);

            // 2. WhatsApp Notification (if configured/enabled)
            try {
                $this->whatsAppService->sendNearbyFoodAlertNotification($donation, $ngo, $distance);
            } catch (\Throwable $e) {
                Log::warning("WhatsApp nearby food alert delivery failure: " . $e->getMessage());
            }

            $alertedCount++;
        }

        return $alertedCount;
    }
}
