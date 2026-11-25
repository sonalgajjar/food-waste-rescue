<?php

namespace App\Http\Controllers\NGO;

use App\Http\Controllers\Controller;
use App\Models\FoodDonation;
use App\Models\PickupRequest;
use App\Models\NGO;
use App\Models\Notification;
use App\Http\Requests\StorePickupRequest;
use App\Services\WhatsAppService;
use App\Services\FoodMetricsService;
use App\Services\PickupWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NGOController extends Controller
{
    protected WhatsAppService $whatsAppService;
    protected PickupWorkflowService $workflowService;
    protected FoodMetricsService $metricsService;

    public function __construct(
        WhatsAppService $whatsAppService,
        PickupWorkflowService $workflowService,
        FoodMetricsService $metricsService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->workflowService = $workflowService;
        $this->metricsService = $metricsService;
    }

    /**
     * Get current authenticated user's NGO profile.
     */
    private function getNGO(): NGO
    {
        $ngo = auth()->user()->ngo;
        if (!$ngo && !in_array(request()->route()->getName(), ['ngo.profile', 'ngo.profile.update'])) {
            throw new HttpResponseException(redirect()->route('ngo.profile')->with('error', 'Please complete your NGO profile before continuing.'));
        }
        return $ngo;
    }

    /**
     * NGO Dashboard.
     */
    public function dashboard()
    {
        $ngo = $this->getNGO();

        $nearbyDonationsCount = FoodDonation::where('status', 'available')
            ->where('expiry_time', '>', Carbon::now())
            ->count();

        $totalRequests = PickupRequest::where('ngo_id', $ngo->id)->count();
        $pendingRequests = PickupRequest::where('ngo_id', $ngo->id)->where('status', 'pending')->count();
        $acceptedRequests = PickupRequest::where('ngo_id', $ngo->id)->where('status', 'accepted')->count();
        $completedPickups = PickupRequest::where('ngo_id', $ngo->id)->where('status', 'completed')->count();
        $cancelledRequests = PickupRequest::where('ngo_id', $ngo->id)->where('status', 'rejected')->count();

        // Calculate metrics using standardized service
        $metrics = $this->metricsService->calculateMetrics(
            FoodDonation::whereHas('pickupRequests', function ($q) use ($ngo) {
                $q->where('ngo_id', $ngo->id);
            })
        );
        $totalMealsRescued = $metrics['estimated_meals'];

        // Available donations for map
        $mapDonations = FoodDonation::with('restaurant')
            ->where('status', 'available')
            ->where('expiry_time', '>', Carbon::now())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->take(25)
            ->get();

        $recentRequests = PickupRequest::with(['foodDonation.restaurant'])
            ->where('ngo_id', $ngo->id)
            ->latest()
            ->take(6)
            ->get();

        // Recent nearby food alerts for this NGO user
        $recentFoodAlerts = Notification::where('user_id', auth()->id())
            ->where('type', 'food_alert')
            ->where('is_read', false)
            ->latest()
            ->take(3)
            ->get();

        // Monthly chart data (past 6 months of completed / active pickups)
        $chartLabels = [];
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chartLabels[] = $month->format('M Y');
            $chartData[] = PickupRequest::where('ngo_id', $ngo->id)
                ->whereIn('status', ['accepted', 'picked', 'completed'])
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return view('ngo.dashboard', compact(
            'ngo',
            'nearbyDonationsCount',
            'totalRequests',
            'pendingRequests',
            'acceptedRequests',
            'completedPickups',
            'cancelledRequests',
            'totalMealsRescued',
            'mapDonations',
            'recentRequests',
            'recentFoodAlerts',
            'chartLabels',
            'chartData',
            'metrics'
        ));
    }

    /**
     * Browse Nearby Food Listings with Haversine distance, filters, and sorting.
     */
    public function listings(Request $request)
    {
        $ngo = $this->getNGO();

        // Query only unexpired and available food
        $query = FoodDonation::with('restaurant')
            ->where('status', 'available')
            ->where('expiry_time', '>', Carbon::now());

        $hasLocation = ($ngo->latitude !== null && $ngo->longitude !== null);

        // Haversine Distance Calculation
        if ($hasLocation) {
            $lat = (float) $ngo->latitude;
            $lon = (float) $ngo->longitude;

            // Database driver-safe clamp between -1 and 1 to prevent rounding NaN
            if (DB::getDriverName() === 'sqlite') {
                $query->select('*')
                    ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [$lat, $lon, $lat]);
            } else {
                $query->select('*')
                    ->selectRaw("(6371 * acos(LEAST(1.0, GREATEST(-1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))) AS distance", [$lat, $lon, $lat]);
            }

            // Distance filter: 5km, 10km, 25km, 50km
            if ($request->filled('distance') && is_numeric($request->distance) && (int)$request->distance > 0) {
                $maxDistance = (int) $request->distance;
                $query->having('distance', '<=', $maxDistance);
            }
        }

        // Search Keyword Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('food_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('restaurant', function ($r) use ($search) {
                      $r->where('restaurant_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                  });
            });
        }

        // Category Filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Food Type Filter
        if ($request->filled('food_type') && $request->food_type !== 'all') {
            $query->where('food_type', $request->food_type);
        }

        // Sorting
        $sort = $request->input('sort', ($hasLocation ? 'nearest' : 'newest'));

        if ($sort === 'nearest' && $hasLocation) {
            $query->orderBy('distance', 'asc');
        } elseif ($sort === 'expiring') {
            $query->orderBy('expiry_time', 'asc');
        } elseif ($sort === 'quantity') {
            $query->orderBy('quantity', 'desc');
        } else {
            $query->latest();
        }

        $donations = $query->paginate(12);

        return view('ngo.listings', compact('donations', 'ngo', 'hasLocation'));
    }

    /**
     * View Food Donation Details.
     */
    public function showDonation($id)
    {
        $ngo = $this->getNGO();
        $donation = FoodDonation::with('restaurant')->findOrFail($id);

        $existingRequest = PickupRequest::where('food_donation_id', $donation->id)
            ->where('ngo_id', $ngo->id)
            ->first();

        // Calculate distance if coordinates available
        $distanceKm = $donation->calculateDistanceTo($ngo->latitude, $ngo->longitude);

        return view('ngo.show', compact('donation', 'ngo', 'existingRequest', 'distanceKm'));
    }

    /**
     * Request Food Pickup with strict validation, concurrency lock, and central workflow service.
     */
    public function requestPickup(StorePickupRequest $request, $id)
    {
        $ngo = $this->getNGO();
        $this->authorize('create', PickupRequest::class);

        $validated = $request->validated();
        $donation = FoodDonation::with('restaurant.user')->findOrFail($id);

        try {
            $this->workflowService->requestPickup($donation, $ngo, $validated, auth()->user());
            return redirect()->route('ngo.history')->with('success', 'Pickup request submitted successfully! Awaiting restaurant acceptance.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel pending request.
     */
    public function cancelRequest(Request $request, $id)
    {
        $pickupRequest = PickupRequest::with(['foodDonation', 'ngo'])->findOrFail($id);
        $this->authorize('cancel', $pickupRequest);

        try {
            $this->workflowService->rejectRequest($pickupRequest, auth()->user(), 'Cancelled by requesting NGO.');
            return back()->with('success', 'Pickup request cancelled.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update NGO Pickup Request status in validated workflow.
     * Allowed:
     * - 'out_for_pickup': NGO starts transit to restaurant
     * - 'completed': completes redistribution (ONLY if handover_confirmed!)
     *
     * STRICT OTP BYPASS PREVENTION:
     * Direct transitions to 'picked' are completely BLOCKED. Handover MUST be verified via OTP or QR code.
     */
    public function updateRequestStatus(Request $request, $id)
    {
        $ngo = $this->getNGO();
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant.user', 'ngo'])
            ->where('ngo_id', $ngo->id)
            ->findOrFail($id);

        $this->authorize('update', $pickupRequest);

        $request->validate([
            'status' => 'required|string|in:out_for_pickup,completed,picked',
            'remarks' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->status;

        // STRICT OTP BYPASS PREVENTION:
        // NGO cannot directly mark request as 'picked' without OTP/QR verification
        if ($newStatus === 'picked') {
            return back()->with('error', "Direct status updates to 'picked' are forbidden. Food collection handover requires physical OTP or QR code verification.");
        }

        try {
            if ($newStatus === 'out_for_pickup') {
                $this->workflowService->startPickup($pickupRequest, auth()->user());
                return back()->with('success', 'Pickup status updated: Out for Pickup. Please present the OTP or scan the QR code upon arrival at the restaurant.');
            } elseif ($newStatus === 'completed') {
                $this->workflowService->completePickup($pickupRequest, auth()->user());
                return back()->with('success', 'Food distribution successfully marked as completed!');
            }
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('error', "Invalid status transition to '{$newStatus}'.");
    }

    /**
     * View NGO pickup history.
     */
    public function history()
    {
        $ngo = $this->getNGO();
        $requests = PickupRequest::with(['foodDonation.restaurant', 'verification', 'certificate', 'reviews', 'messages'])
            ->where('ngo_id', $ngo->id)
            ->latest()
            ->paginate(15);

        return view('ngo.history', compact('requests', 'ngo'));
    }

    /**
     * View NGO Profile.
     */
    public function profile()
    {
        $ngo = auth()->user()->ngo;
        return view('ngo.profile', compact('ngo'));
    }

    /**
     * Update NGO Profile.
     */
    public function updateProfile(Request $request)
    {
        $ngo = auth()->user()->ngo;

        $validated = $request->validate([
            'ngo_name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:100',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'description' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($ngo && $ngo->logo && Storage::disk('public')->exists($ngo->logo)) {
                Storage::disk('public')->delete($ngo->logo);
            }
            $validated['logo'] = $request->file('logo')->store('ngos', 'public');
        }

        if ($ngo) {
            $ngo->update($validated);
        } else {
            $validated['user_id'] = auth()->id();
            $validated['status'] = 'pending';
            NGO::create($validated);
        }

        return back()->with('success', 'NGO profile updated successfully!');
    }
}
