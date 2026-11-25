<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\NGO;
use App\Models\Notification;
use App\Services\WhatsAppService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, ?WhatsAppService $whatsAppService = null): RedirectResponse
    {
        $whatsAppService = $whatsAppService ?? app(WhatsAppService::class);
        $rules = [
            'role' => ['required', 'string', 'in:restaurant,ngo'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        if ($request->role === 'restaurant') {
            $rules = array_merge($rules, [
                'restaurant_name' => ['required', 'string', 'max:255'],
                'owner_name' => ['required', 'string', 'max:255'],
                'license_number' => ['required', 'string', 'max:100', 'unique:restaurants,license_number'],
                'gst_number' => ['nullable', 'string', 'max:100', 'unique:restaurants,gst_number'],
                'license_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'license_expires_at' => ['nullable', 'date'],
                'opening_time' => ['required', 'string'],
                'closing_time' => ['required', 'string'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
        } elseif ($request->role === 'ngo') {
            $rules = array_merge($rules, [
                'ngo_name' => ['required', 'string', 'max:255'],
                'registration_number' => ['required', 'string', 'max:100', 'unique:n_g_o_s,registration_number'],
                'owner_name_ngo' => ['nullable', 'string', 'max:255'],
                'registration_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'fcra_number' => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);
        }

        $validated = $request->validate($rules);

        // Determine coordinates: prefer client-provided validated GPS coordinates,
        // then try geocoding, and gracefully fall back to NULL (never silently assign dummy coordinates)
        $latitude = null;
        $longitude = null;

        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $latitude = (float) $validated['latitude'];
            $longitude = (float) $validated['longitude'];
        } else {
            try {
                $fullAddress = "{$request->address}, {$request->city}, {$request->state}, {$request->pincode}";
                $response = Http::timeout(3)
                    ->withHeaders(['User-Agent' => 'FoodWasteRescue/1.0'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $fullAddress,
                        'format' => 'json',
                        'limit' => 1,
                    ]);

                if ($response->successful() && !empty($response->json()) && isset($response->json()[0]['lat'])) {
                    $latitude = (float) $response->json()[0]['lat'];
                    $longitude = (float) $response->json()[0]['lon'];
                } else {
                    // Fallback to city and state
                    $cityResponse = Http::timeout(2)
                        ->withHeaders(['User-Agent' => 'FoodWasteRescue/1.0'])
                        ->get('https://nominatim.openstreetmap.org/search', [
                            'q' => "{$request->city}, {$request->state}",
                            'format' => 'json',
                            'limit' => 1,
                        ]);

                    if ($cityResponse->successful() && !empty($cityResponse->json()) && isset($cityResponse->json()[0]['lat'])) {
                        $latitude = (float) $cityResponse->json()[0]['lat'];
                        $longitude = (float) $cityResponse->json()[0]['lon'];
                    }
                }
            } catch (\Throwable $e) {
                Log::info("Geocoding lookup skipped/failed during registration: " . $e->getMessage());
                // Remains null gracefully
            }
        }

        // Create User and Profile in transaction
        $user = DB::transaction(function () use ($request, $latitude, $longitude) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => 'pending',
            ]);

            if ($request->role === 'restaurant') {
                $licenseDocPath = null;
                if ($request->hasFile('license_document')) {
                    $licenseDocPath = $request->file('license_document')->store('documents/licenses', 'local');
                }

                Restaurant::create([
                    'user_id' => $user->id,
                    'restaurant_name' => $request->restaurant_name,
                    'owner_name' => $request->owner_name,
                    'license_number' => $request->license_number,
                    'license_document' => $licenseDocPath,
                    'license_expires_at' => $request->license_expires_at,
                    'gst_number' => $request->gst_number,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'pincode' => $request->pincode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'opening_time' => $request->opening_time,
                    'closing_time' => $request->closing_time,
                    'description' => $request->description ?? '',
                    'status' => 'pending',
                ]);
            } elseif ($request->role === 'ngo') {
                $regDocPath = null;
                if ($request->hasFile('registration_document')) {
                    $regDocPath = $request->file('registration_document')->store('documents/ngos', 'local');
                }

                NGO::create([
                    'user_id' => $user->id,
                    'ngo_name' => $request->ngo_name,
                    'registration_number' => $request->registration_number,
                    'registration_document' => $regDocPath,
                    'fcra_number' => $request->fcra_number,
                    'owner_name' => $request->owner_name_ngo ?? $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'pincode' => $request->pincode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'description' => $request->description ?? '',
                    'status' => 'pending',
                ]);
            }

            // Create in-app notification for admin
            $adminUser = User::where('role', 'admin')->first();
            if ($adminUser) {
                Notification::create([
                    'user_id' => $adminUser->id,
                    'title' => 'New Partner Registration',
                    'message' => "A new {$request->role} ({$request->name}) registered and is awaiting your review and approval.",
                    'is_read' => false,
                ]);
            }

            return $user;
        });

        // Trigger WhatsApp welcome/acknowledgement safely
        try {
            $whatsAppService->sendNewRegistrationNotification($user);
        } catch (\Throwable $e) {
            Log::warning("WhatsApp registration notification error: " . $e->getMessage());
        }

        event(new Registered($user));

        return redirect()->route('login')->with('success', 'Registration submitted successfully! Your account is currently pending administrator verification.');
    }
}
