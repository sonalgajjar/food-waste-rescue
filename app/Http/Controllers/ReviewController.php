<?php

namespace App\Http\Controllers;

use App\Models\DonationReview;
use App\Models\Notification;
use App\Models\PickupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Store a new rating & review for a completed donation pickup.
     */
    public function store(Request $request, $pickupRequestId)
    {
        $user = auth()->user();
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant.user', 'ngo.user'])->findOrFail($pickupRequestId);

        if ($pickupRequest->status !== 'completed') {
            return back()->with('error', 'Reviews can only be submitted for completed donations.');
        }

        $isRestaurant = ($user->role === 'restaurant' && $user->restaurant?->id === $pickupRequest->foodDonation->restaurant_id);
        $isNgo = ($user->role === 'ngo' && $user->ngo?->id === $pickupRequest->ngo_id);

        if (!$isRestaurant && !$isNgo) {
            abort(403, 'Unauthorized: Only participants of this donation can leave a review.');
        }

        $reviewerRole = $isRestaurant ? 'restaurant' : 'ngo';

        // Check for duplicate review
        $existingReview = DonationReview::where('pickup_request_id', $pickupRequest->id)
            ->where('reviewer_role', $reviewerRole)
            ->first();

        if ($existingReview) {
            return back()->with('error', 'You have already submitted a review for this donation.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string|max:1000',
            'criterion_1' => 'nullable|integer|between:1,5',
            'criterion_2' => 'nullable|integer|between:1,5',
            'criterion_3' => 'nullable|integer|between:1,5',
        ]);

        $criteria = [
            'criterion_1' => (int) ($validated['criterion_1'] ?? $validated['rating']),
            'criterion_2' => (int) ($validated['criterion_2'] ?? $validated['rating']),
            'criterion_3' => (int) ($validated['criterion_3'] ?? $validated['rating']),
        ];

        DB::transaction(function () use ($pickupRequest, $user, $reviewerRole, $validated, $criteria, $isRestaurant) {
            DonationReview::create([
                'pickup_request_id' => $pickupRequest->id,
                'food_donation_id' => $pickupRequest->food_donation_id,
                'reviewer_id' => $user->id,
                'reviewer_role' => $reviewerRole,
                'restaurant_id' => $pickupRequest->foodDonation->restaurant_id,
                'ngo_id' => $pickupRequest->ngo_id,
                'rating' => (int) $validated['rating'],
                'criteria' => $criteria,
                'review' => $validated['review'] ?? null,
                'status' => 'approved',
            ]);

            // Notify counterpart
            $recipientUser = $isRestaurant ? $pickupRequest->ngo?->user : $pickupRequest->foodDonation?->restaurant?->user;
            if ($recipientUser) {
                Notification::create([
                    'user_id' => $recipientUser->id,
                    'type' => 'new_review',
                    'title' => 'New Rating & Review Received',
                    'message' => "{$user->name} rated you {$validated['rating']} stars for donation \"{$pickupRequest->foodDonation->food_name}\".",
                    'link' => ($recipientUser->role === 'restaurant') ? route('restaurant.history') : route('ngo.history'),
                    'food_donation_id' => $pickupRequest->food_donation_id,
                    'is_read' => false,
                ]);
            }
        });

        return back()->with('success', 'Thank you! Your rating and review have been submitted successfully.');
    }

    /**
     * Admin view all reviews for moderation.
     */
    public function adminIndex()
    {
        $reviews = DonationReview::with([
            'reviewer',
            'restaurant',
            'ngo',
            'foodDonation',
        ])->latest()->paginate(20);

        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Admin toggle review visibility (approve / hide).
     */
    public function toggleStatus($id)
    {
        $review = DonationReview::findOrFail($id);
        $review->status = ($review->status === 'approved') ? 'hidden' : 'approved';
        $review->save();

        return back()->with('success', "Review #{$review->id} status updated to {$review->status}.");
    }
}
