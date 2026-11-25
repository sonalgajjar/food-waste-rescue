<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FoodDonation;
use App\Models\Restaurant;
use App\Models\NGO;
use App\Models\Contact;
use App\Models\Notification;
use App\Http\Requests\StoreContactRequest;
use App\Services\FoodMetricsService;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    /**
     * Display the Home page with live metrics and active listings.
     */
    public function home()
    {
        $totalDonationsCount = FoodDonation::count();
        $donationsQuery = FoodDonation::whereIn('status', ['picked', 'completed']);
        $metrics = app(FoodMetricsService::class)->calculateMetrics($donationsQuery);
        $totalMealsRescued = $metrics['total_meals_rescued'];
        $totalCO2Saved = $metrics['co2_saved_kg'];
        $activeRestaurantsCount = Restaurant::where('status', 'active')->count();
        $activeNgosCount = NGO::where('status', 'active')->count();

        $recentDonations = FoodDonation::with('restaurant')
            ->where('status', 'available')
            ->latest()
            ->take(6)
            ->get();

        return view('frontend.home', compact(
            'totalDonationsCount',
            'totalMealsRescued',
            'totalCO2Saved',
            'activeRestaurantsCount',
            'activeNgosCount',
            'recentDonations'
        ));
    }

    /**
     * Display About Us page.
     */
    public function about()
    {
        return view('frontend.about');
    }

    /**
     * Display Services page.
     */
    public function services()
    {
        return view('frontend.services');
    }

    /**
     * Display How It Works page.
     */
    public function howItWorks()
    {
        return view('frontend.how-it-works');
    }

    /**
     * Display FAQ page.
     */
    public function faq()
    {
        return view('frontend.faq');
    }

    /**
     * Display Contact page.
     */
    public function contact()
    {
        return view('frontend.contact');
    }

    /**
     * Store contact message.
     */
    public function storeContact(StoreContactRequest $request)
    {
        $validated = $request->validated();

        Contact::create($validated);

        return redirect()->route('contact')->with('success', 'Thank you for reaching out! Your message has been sent to our team.');
    }

    /**
     * Display Privacy Policy.
     */
    public function privacy()
    {
        return view('frontend.privacy');
    }

    /**
     * Display Terms & Conditions.
     */
    public function terms()
    {
        return view('frontend.terms');
    }

    /**
     * Mark Notification as read.
     */
    public function readNotification(Request $request, $id)
    {
        if ($request->has('mark_all')) {
            Notification::where('user_id', auth()->id())->update(['is_read' => true]);
            return back()->with('success', 'All notifications marked as read.');
        }

        $notification = Notification::where('user_id', auth()->id())->where('id', $id)->firstOrFail();
        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }
}
