<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\FoodDonation;
use App\Models\RecurringDonation;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class RecurringDonationController extends Controller
{
    private function getRestaurant(): Restaurant
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            throw new HttpResponseException(redirect()->route('restaurant.profile')->with('error', 'Please complete your restaurant profile before continuing.'));
        }
        return $restaurant;
    }

    /**
     * Display list of recurring donation schedules.
     */
    public function index()
    {
        $restaurant = $this->getRestaurant();

        $schedules = RecurringDonation::where('restaurant_id', $restaurant->id)
            ->latest()
            ->paginate(10);

        // Fetch auto-generated donation history
        $generatedDonations = FoodDonation::where('restaurant_id', $restaurant->id)
            ->where('description', 'like', '%[Scheduled via:%')
            ->latest()
            ->take(8)
            ->get();

        return view('restaurant.recurring.index', compact('schedules', 'restaurant', 'generatedDonations'));
    }

    /**
     * Store new recurring donation schedule.
     */
    public function store(Request $request)
    {
        $restaurant = $this->getRestaurant();

        $validated = $request->validate([
            'schedule_name' => 'required|string|max:255',
            'food_name' => 'required|string|max:255',
            'category' => 'required|string|in:Veg,Non-Veg,Vegan,Dairy,Bakery,Groceries',
            'food_type' => 'required|string|in:Cooked Meals,Packaged Goods,Raw Ingredients,Fresh Produce,Baked Goods',
            'quantity' => 'required|numeric|min:0.5',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'pickup_address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'frequency' => 'required|string|in:daily,weekly,selected_days',
            'selected_days' => 'nullable|array',
            'selected_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'pickup_time_of_day' => 'required|date_format:H:i',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        RecurringDonation::create([
            'restaurant_id' => $restaurant->id,
            'schedule_name' => $validated['schedule_name'],
            'food_name' => $validated['food_name'],
            'category' => $validated['category'],
            'food_type' => $validated['food_type'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'description' => $validated['description'] ?? null,
            'pickup_address' => $validated['pickup_address'],
            'latitude' => $validated['latitude'] ?? $restaurant->latitude,
            'longitude' => $validated['longitude'] ?? $restaurant->longitude,
            'frequency' => $validated['frequency'],
            'selected_days' => ($validated['frequency'] === 'selected_days') ? ($validated['selected_days'] ?? []) : null,
            'pickup_time_of_day' => $validated['pickup_time_of_day'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('restaurant.recurring.index')->with('success', 'Recurring donation schedule created successfully! Donations will automatically generate on due dates.');
    }

    /**
     * Toggle active / paused status.
     */
    public function toggle($id)
    {
        $restaurant = $this->getRestaurant();
        $schedule = RecurringDonation::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $schedule->is_active = !$schedule->is_active;
        $schedule->save();

        $statusMsg = $schedule->is_active ? 'resumed' : 'paused';
        return back()->with('success', "Schedule \"{$schedule->schedule_name}\" has been {$statusMsg}.");
    }

    /**
     * Delete recurring schedule.
     */
    public function destroy($id)
    {
        $restaurant = $this->getRestaurant();
        $schedule = RecurringDonation::where('restaurant_id', $restaurant->id)->findOrFail($id);

        $schedule->delete();

        return redirect()->route('restaurant.recurring.index')->with('success', 'Recurring schedule deleted.');
    }
}
