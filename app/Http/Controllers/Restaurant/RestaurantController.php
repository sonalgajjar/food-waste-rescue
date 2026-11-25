<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\FoodDonation;
use App\Models\PickupRequest;
use App\Models\Restaurant;
use App\Models\Notification;
use App\Services\WhatsAppService;
use App\Services\FoodAlertService;
use App\Services\FoodMetricsService;
use App\Services\PickupWorkflowService;
use App\Events\FoodDonationCreated;
use Illuminate\Http\Request;
use App\Http\Requests\StoreFoodRequest;
use App\Http\Requests\UpdateFoodRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RestaurantController extends Controller
{
    protected WhatsAppService $whatsAppService;
    protected FoodAlertService $foodAlertService;
    protected PickupWorkflowService $workflowService;
    protected FoodMetricsService $metricsService;

    public function __construct(
        WhatsAppService $whatsAppService,
        FoodAlertService $foodAlertService,
        PickupWorkflowService $workflowService,
        FoodMetricsService $metricsService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->foodAlertService = $foodAlertService;
        $this->workflowService = $workflowService;
        $this->metricsService = $metricsService;
    }

    /**
     * Get current authenticated user's restaurant profile.
     */
    private function getRestaurant(): Restaurant
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant && !in_array(request()->route()->getName(), ['restaurant.profile', 'restaurant.profile.update'])) {
            throw new HttpResponseException(redirect()->route('restaurant.profile')->with('error', 'Please complete your restaurant profile before continuing.'));
        }
        return $restaurant;
    }

    /**
     * Restaurant Dashboard.
     */
    public function dashboard()
    {
        $restaurant = $this->getRestaurant();

        $totalDonations = FoodDonation::where('restaurant_id', $restaurant->id)->count();
        $availableDonations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->where('status', 'available')
            ->where('expiry_time', '>', Carbon::now())
            ->count();
        $reservedDonations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['requested', 'approved'])
            ->count();
        $completedDonations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['picked', 'completed'])
            ->count();
        $expiredDonations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->where(function ($q) {
                $q->where('status', 'expired')
                  ->orWhere('expiry_time', '<=', Carbon::now());
            })
            ->count();

        $pendingPickupRequests = PickupRequest::whereHas('foodDonation', function ($q) use ($restaurant) {
            $q->where('restaurant_id', $restaurant->id);
        })->where('status', 'pending')->count();

        $recentDonations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->latest()
            ->take(6)
            ->get();

        $recentRequests = PickupRequest::with(['ngo.user', 'foodDonation'])
            ->whereHas('foodDonation', function ($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id);
            })
            ->latest()
            ->take(5)
            ->get();

        // Monthly chart data (past 6 months)
        $chartLabels = [];
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chartLabels[] = $month->format('M Y');
            $chartData[] = FoodDonation::where('restaurant_id', $restaurant->id)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $metrics = $this->metricsService->calculateMetrics(FoodDonation::where('restaurant_id', $restaurant->id));

        return view('restaurant.dashboard', compact(
            'restaurant',
            'totalDonations',
            'availableDonations',
            'reservedDonations',
            'completedDonations',
            'expiredDonations',
            'pendingPickupRequests',
            'recentDonations',
            'recentRequests',
            'chartLabels',
            'chartData',
            'metrics'
        ));
    }

    /**
     * Display listing of food donations created by restaurant.
     */
    public function index()
    {
        $restaurant = $this->getRestaurant();
        $donations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->latest()
            ->paginate(15);

        return view('restaurant.donations.index', compact('donations', 'restaurant'));
    }

    /**
     * Show create food donation form.
     */
    public function create()
    {
        $restaurant = $this->getRestaurant();
        return view('restaurant.donations.create', compact('restaurant'));
    }

    /**
     * Store new food donation.
     */
    public function store(StoreFoodRequest $request)
    {
        $restaurant = $this->getRestaurant();
        $this->authorize('create', FoodDonation::class);

        if ($restaurant->isLicenseExpired()) {
            return back()->with('error', 'Your FSSAI food business license has expired. Please renew your license documents in your profile before posting new donations.')->withInput();
        }

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('donations', 'public');
        }

        $donation = FoodDonation::create([
            'restaurant_id' => $restaurant->id,
            'food_name' => $validated['food_name'],
            'category' => $validated['category'],
            'food_type' => $validated['food_type'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'description' => $validated['description'] ?? '',
            'cooked_time' => $validated['cooked_time'],
            'expiry_time' => $validated['expiry_time'],
            'pickup_address' => $validated['pickup_address'],
            'latitude' => $validated['latitude'] ?? $restaurant->latitude,
            'longitude' => $validated['longitude'] ?? $restaurant->longitude,
            'image' => $imagePath,
            'allergen_info' => $validated['allergen_info'] ?? null,
            'storage_instructions' => $validated['storage_instructions'] ?? null,
            'packaging_condition' => $validated['packaging_condition'] ?? 'Sealed Food-Grade Containers',
            'pickup_instructions' => $validated['pickup_instructions'] ?? null,
            'food_safety_declaration' => $request->boolean('food_safety_declaration', true),
            'safety_declared_at' => Carbon::now(),
            'safety_declared_by_user_id' => auth()->id(),
            'is_veg' => in_array($validated['category'], ['Veg', 'Vegan', 'Dairy']),
            'status' => 'available',
        ]);

        // Alert nearby active NGOs within alert radius
        $alertedCount = $this->foodAlertService->notifyNearbyNgos($donation);

        // Fire food donation created event
        event(new FoodDonationCreated($donation, $alertedCount));

        $successMsg = 'Food donation posted successfully!';
        if ($alertedCount > 0) {
            $successMsg .= " {$alertedCount} nearby NGO" . ($alertedCount === 1 ? '' : 's') . " alerted.";
        }

        return redirect()->route('restaurant.donations.index')->with('success', $successMsg);
    }

    /**
     * Show edit form for donation.
     */
    public function edit($id)
    {
        $restaurant = $this->getRestaurant();
        $donation = FoodDonation::findOrFail($id);
        
        $this->authorize('update', $donation);

        return view('restaurant.donations.edit', compact('donation', 'restaurant'));
    }

    /**
     * Update food donation.
     */
    public function update(UpdateFoodRequest $request, $id)
    {
        $restaurant = $this->getRestaurant();
        $donation = FoodDonation::findOrFail($id);
        
        $this->authorize('update', $donation);

        if (!in_array($donation->status, ['available', 'expired'])) {
            return back()->with('error', 'Donation details cannot be modified while a pickup request is active.');
        }

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($donation->image && Storage::disk('public')->exists($donation->image)) {
                Storage::disk('public')->delete($donation->image);
            }
            $validated['image'] = $request->file('image')->store('donations', 'public');
        }

        $donation->update($validated);

        return redirect()->route('restaurant.donations.index')->with('success', 'Food donation updated successfully!');
    }

    /**
     * Delete food donation.
     */
    public function destroy($id)
    {
        $restaurant = $this->getRestaurant();
        $donation = FoodDonation::findOrFail($id);
        
        $this->authorize('delete', $donation);

        if ($donation->image && Storage::disk('public')->exists($donation->image)) {
            Storage::disk('public')->delete($donation->image);
        }

        $donation->delete();

        return redirect()->route('restaurant.donations.index')->with('success', 'Donation deleted successfully!');
    }

    /**
     * View history of donations & requests.
     */
    public function history()
    {
        $restaurant = $this->getRestaurant();
        $requests = PickupRequest::with(['ngo.user', 'foodDonation.restaurant', 'verification', 'certificate', 'reviews', 'messages'])
            ->whereHas('foodDonation', function ($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id);
            })
            ->latest()
            ->paginate(15);

        return view('restaurant.history', compact('requests', 'restaurant'));
    }

    /**
     * Update NGO Pickup Request status in validated workflow.
     * Allowed actions:
     * - 'accepted': accepts pending request & creates OTP/QR
     * - 'rejected': declines pending or accepted request
     *
     * STRICT OTP BYPASS PREVENTION:
     * Direct transitions to 'picked' or 'completed' are completely BLOCKED.
     * Handover must pass through physical OTP / QR code verification.
     */
    public function updateRequestStatus(Request $request, $id)
    {
        $restaurant = $this->getRestaurant();
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant', 'ngo.user'])
            ->whereHas('foodDonation', function ($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id);
            })
            ->findOrFail($id);

        $this->authorize('update', $pickupRequest);

        $request->validate([
            'status' => 'required|in:accepted,rejected,picked,completed',
            'remarks' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->status;

        // STRICT OTP BYPASS PREVENTION:
        // Restaurant cannot mark as picked or completed via this status endpoint.
        if (in_array($newStatus, ['picked', 'completed'])) {
            return back()->with('error', "Direct status updates to '{$newStatus}' are forbidden. Handover must be verified via physical OTP or QR code verification.");
        }

        try {
            if ($newStatus === 'accepted') {
                $this->workflowService->acceptRequest($pickupRequest, auth()->user(), $request->remarks);
                return back()->with('success', 'Pickup request accepted successfully! Your handover OTP and QR code have been generated.');
            } elseif ($newStatus === 'rejected') {
                $this->workflowService->rejectRequest($pickupRequest, auth()->user(), $request->remarks ?? 'Declined by restaurant');
                return back()->with('success', 'Pickup request declined. Food donation has been released back to available.');
            }
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('error', 'Invalid status update requested.');
    }

    /**
     * View Profile.
     */
    public function profile()
    {
        $restaurant = auth()->user()->restaurant;
        return view('restaurant.profile', compact('restaurant'));
    }

    /**
     * Update Profile.
     */
    public function updateProfile(Request $request)
    {
        $restaurant = auth()->user()->restaurant;

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'license_number' => 'required|string|max:100',
            'gst_number' => 'nullable|string|max:100',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'opening_time' => 'nullable|string',
            'closing_time' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($restaurant && $restaurant->logo && Storage::disk('public')->exists($restaurant->logo)) {
                Storage::disk('public')->delete($restaurant->logo);
            }
            $validated['logo'] = $request->file('logo')->store('restaurants', 'public');
        }

        if ($restaurant) {
            $restaurant->update($validated);
        } else {
            $validated['user_id'] = auth()->id();
            $validated['status'] = 'pending';
            Restaurant::create($validated);
        }

        return back()->with('success', 'Restaurant profile updated successfully!');
    }
}
