<?php

namespace App\Services;

use App\Events\FoodDonationCreated;
use App\Models\FoodDonation;
use App\Models\Notification;
use App\Models\RecurringDonation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringDonationService
{
    public function __construct(
        protected FoodAlertService $foodAlertService
    ) {}

    /**
     * Process all active recurring donation schedules due for today.
     *
     * @return array{processed: int, generated: int, skipped: int}
     */
    public function processDueSchedules(): array
    {
        $today = Carbon::today();
        $dayOfWeek = $today->format('l'); // e.g. "Monday"

        $schedules = RecurringDonation::with('restaurant.user')
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->get();

        $stats = ['processed' => 0, 'generated' => 0, 'skipped' => 0];

        foreach ($schedules as $schedule) {
            $stats['processed']++;

            // 1. Check if schedule applies to today based on frequency
            if (!$this->shouldRunToday($schedule, $dayOfWeek)) {
                $stats['skipped']++;
                continue;
            }

            // 2. Prevent duplicate generation for the same schedule and date
            $alreadyGenerated = FoodDonation::where('restaurant_id', $schedule->restaurant_id)
                ->whereDate('created_at', $today)
                ->where('food_name', $schedule->food_name)
                ->exists();

            if ($alreadyGenerated || ($schedule->last_generated_at && $schedule->last_generated_at->isToday())) {
                $stats['skipped']++;
                continue;
            }

            // 3. Create the Food Donation safely
            try {
                DB::transaction(function () use ($schedule, $today, &$stats) {
                    $cookedTime = Carbon::parse($today->format('Y-m-d') . ' ' . $schedule->pickup_time_of_day);
                    $expiryTime = (clone $cookedTime)->addHours(4); // Default 4 hours freshness window

                    $donation = FoodDonation::create([
                        'restaurant_id' => $schedule->restaurant_id,
                        'food_name' => $schedule->food_name,
                        'category' => $schedule->category,
                        'food_type' => $schedule->food_type,
                        'quantity' => $schedule->quantity,
                        'unit' => $schedule->unit,
                        'description' => ($schedule->description ? $schedule->description . ' | ' : '') . "[Scheduled via: {$schedule->schedule_name}]",
                        'cooked_time' => $cookedTime,
                        'expiry_time' => $expiryTime,
                        'pickup_address' => $schedule->pickup_address,
                        'latitude' => $schedule->latitude ?? $schedule->restaurant?->latitude,
                        'longitude' => $schedule->longitude ?? $schedule->restaurant?->longitude,
                        'status' => 'available',
                    ]);

                    // Update last generated timestamp on schedule
                    $schedule->update([
                        'last_generated_at' => Carbon::now(),
                    ]);

                    // Alert nearby NGOs
                    $alertedCount = $this->foodAlertService->notifyNearbyNgos($donation);

                    // Fire donation created event
                    event(new FoodDonationCreated($donation, $alertedCount));

                    // In-app notification to restaurant donor confirming auto-post
                    if ($schedule->restaurant?->user_id) {
                        Notification::create([
                            'user_id' => $schedule->restaurant->user_id,
                            'type' => 'recurring_post',
                            'title' => 'Scheduled Food Posted',
                            'message' => "Your recurring schedule \"{$schedule->schedule_name}\" has automatically posted {$donation->quantity} {$donation->unit} of {$donation->food_name}. Nearby NGOs have been alerted.",
                            'link' => route('restaurant.donations.index'),
                            'food_donation_id' => $donation->id,
                            'is_read' => false,
                        ]);
                    }

                    $stats['generated']++;
                });
            } catch (\Throwable $e) {
                Log::error("Recurring donation generation failed for schedule #{$schedule->id}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Check if schedule frequency matches today.
     */
    public function shouldRunToday(RecurringDonation $schedule, string $dayOfWeek): bool
    {
        if ($schedule->frequency === 'daily') {
            return true;
        }

        if ($schedule->frequency === 'weekly') {
            // Runs on the day of week matching start_date
            return $schedule->start_date->format('l') === $dayOfWeek;
        }

        if ($schedule->frequency === 'selected_days') {
            $days = is_array($schedule->selected_days) ? $schedule->selected_days : [];
            return in_array($dayOfWeek, $days, true);
        }

        return false;
    }
}
