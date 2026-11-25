<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FoodDonation;
use App\Models\PickupRequest;
use App\Models\Notification;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireDonationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire food donations whose expiry time has passed and update associated requests.';

    /**
     * Execute the console command.
     */
    public function handle(?WhatsAppService $whatsAppService = null): int
    {
        $whatsAppService = $whatsAppService ?? app(WhatsAppService::class);
        $now = Carbon::now();

        // Donations that have passed expiry time and are not undergoing active transit/handover
        $expiredDonations = FoodDonation::with(['restaurant.user', 'pickupRequests.ngo.user'])
            ->where('expiry_time', '<=', $now)
            ->whereIn('status', ['available', 'requested', 'approved'])
            ->whereDoesntHave('pickupRequests', function ($q) {
                $q->whereIn('status', ['out_for_pickup', 'handover_confirmed']);
            })
            ->get();

        $count = 0;

        foreach ($expiredDonations as $donation) {
            DB::transaction(function () use ($donation, &$count, $whatsAppService) {
                $donation->update(['status' => 'expired']);
                $count++;

                // Notify restaurant owner
                if ($donation->restaurant && $donation->restaurant->user) {
                    $donorUser = $donation->restaurant->user;
                    Notification::create([
                        'user_id' => $donorUser->id,
                        'title' => 'Food Donation Expired',
                        'message' => 'Your food donation "' . $donation->food_name . '" has expired and is now marked unavailable.',
                        'is_read' => false,
                    ]);

                    try {
                        $whatsAppService->sendFoodExpiryNotification($donation, $donorUser);
                    } catch (\Throwable $e) {
                        Log::warning("WhatsApp expiry alert failed for donor: " . $e->getMessage());
                    }
                }

                // Handle pending and accepted pickup requests
                $activeRequests = $donation->pickupRequests()
                    ->whereIn('status', ['pending', 'accepted'])
                    ->get();

                foreach ($activeRequests as $req) {
                    $req->update([
                        'status' => 'rejected',
                        'remarks' => ($req->remarks ? $req->remarks . ' | ' : '') . 'Automatically cancelled because the food has expired.',
                    ]);

                    if ($req->ngo && $req->ngo->user) {
                        $ngoUser = $req->ngo->user;
                        Notification::create([
                            'user_id' => $ngoUser->id,
                            'title' => 'Pickup Request Cancelled (Expired)',
                            'message' => 'Your pickup request for "' . $donation->food_name . '" from ' . ($donation->restaurant->restaurant_name ?? 'restaurant') . ' was cancelled because the food reached its expiry time.',
                            'is_read' => false,
                        ]);

                        try {
                            $whatsAppService->sendFoodExpiryNotification($donation, $ngoUser);
                        } catch (\Throwable $e) {
                            Log::warning("WhatsApp expiry alert failed for NGO: " . $e->getMessage());
                        }
                    }
                }
            });
        }

        $this->info("Successfully expired {$count} food donation(s) and updated active requests.");
        Log::info("donations:expire command ran: {$count} donation(s) expired.");

        return Command::SUCCESS;
    }
}
