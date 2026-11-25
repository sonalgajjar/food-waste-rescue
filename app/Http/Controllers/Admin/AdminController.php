<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\NGO;
use App\Models\FoodDonation;
use App\Models\PickupRequest;
use App\Models\Contact;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DonationsExport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Mail\ContactReplyMail;
use App\Mail\AccountApprovedMail;
use App\Services\WhatsAppService;

class AdminController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }
    /**
     * Admin Dashboard.
     */
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalRestaurants = Restaurant::count();
        $totalNGOs = NGO::count();
        $totalDonations = FoodDonation::count();
        $totalPickups = PickupRequest::where('status', 'completed')->count();
        $totalContacts = Contact::where('status', 'pending')->count();
        $totalMealsRescued = FoodDonation::whereIn('status', ['picked', 'completed'])->sum('quantity');

        $pendingRestaurants = Restaurant::where('status', 'pending')->latest()->take(5)->get();
        $pendingNGOs = NGO::where('status', 'pending')->latest()->take(5)->get();
        $pendingApprovalsCount = Restaurant::where('status', 'pending')->count() + NGO::where('status', 'pending')->count();
        $recentDonations = FoodDonation::with('restaurant')->latest()->take(6)->get();
        $recentUsers = User::latest()->take(6)->get();

        // Chart data
        $chartLabels = [];
        $chartDonations = [];
        $chartPickups = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chartLabels[] = $month->format('M Y');
            $chartDonations[] = FoodDonation::whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count();
            $chartPickups[] = PickupRequest::where('status', 'completed')->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count();
        }

        // Category breakdown
        $categoryStats = FoodDonation::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
        $categoryLabels = array_keys($categoryStats);
        $categoryCounts = array_values($categoryStats);

        $metrics = (new \App\Services\FoodMetricsService())->calculateMetrics();
        $totalMealsRescued = $metrics['estimated_meals'];
        $totalCo2Kg = $metrics['co2_saved_kg'];
        $completionRate = $totalDonations > 0 ? round(($totalPickups / $totalDonations) * 100, 1) : 0;

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalRestaurants',
            'totalNGOs',
            'totalDonations',
            'totalPickups',
            'totalContacts',
            'totalMealsRescued',
            'totalCo2Kg',
            'completionRate',
            'metrics',
            'pendingRestaurants',
            'pendingNGOs',
            'pendingApprovalsCount',
            'recentDonations',
            'recentUsers',
            'chartLabels',
            'chartDonations',
            'chartPickups',
            'categoryLabels',
            'categoryCounts'
        ));
    }

    /**
     * Manage Users.
     */
    public function users()
    {
        $users = User::latest()->paginate(15);
        return view('admin.users', compact('users'));
    }

    /**
     * Toggle User Status (Active / Inactive).
     */
    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $newStatus = ($user->status === 'active') ? 'inactive' : 'active';
        $wasNotActive = ($user->status !== 'active');

        $user->status = $newStatus;
        $user->save();

        if ($user->restaurant) {
            $user->restaurant->update(['status' => $user->status]);
        }
        if ($user->ngo) {
            $user->ngo->update(['status' => $user->status]);
        }

        $notificationResult = null;
        if ($newStatus === 'active' && $wasNotActive) {
            $notificationResult = $this->sendApprovalNotification($user);
        }

        if ($newStatus === 'active' && $notificationResult) {
            $email = $notificationResult['email'] ?? null;
            if ($email && $email['success']) {
                return back()->with('success', 'User activated and approval email sent to ' . $email['email'] . '.');
            } elseif ($email && !$email['success'] && $email['attempted']) {
                return back()->with('warning', 'User activated. Note: Email notification failed (' . $email['message'] . ').');
            }
        }

        return back()->with('success', 'User status updated to ' . ucfirst($user->status));
    }

    /**
     * Manage Restaurants.
     */
    public function restaurants()
    {
        $restaurants = Restaurant::with('user')->latest()->paginate(15);
        return view('admin.restaurants', compact('restaurants'));
    }

    /**
     * Approve or Reject Restaurant.
     */
    public function approveRestaurant(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,pending,rejected',
            'rejection_reason' => 'nullable|string|max:1000',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        $restaurant = Restaurant::with('user')->findOrFail($id);
        $status = $request->status;
        $wasNotActive = ($restaurant->status !== 'active');
        $user = $restaurant->user;
        $userWasNotActive = $user ? ($user->status !== 'active') : false;

        DB::transaction(function () use ($restaurant, $user, $status, $request) {
            $updateData = ['status' => $status];
            if ($request->filled('rejection_reason')) {
                $updateData['rejection_reason'] = $request->rejection_reason;
            }
            if ($request->filled('verification_notes')) {
                $updateData['verification_notes'] = $request->verification_notes;
            }
            $restaurant->update($updateData);

            if ($user) {
                $user->update(['status' => $status]);
            }
        });

        $notificationResult = null;
        if ($user) {
            if ($status === 'active' && ($wasNotActive || $userWasNotActive)) {
                $notificationResult = $this->sendApprovalNotification($user);
            } elseif ($status === 'rejected') {
                $reasonText = $request->rejection_reason ? " Reason: {$request->rejection_reason}" : " Please contact support.";
                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Registration Rejected',
                    'message' => 'Your restaurant registration for FoodWasteRescue was reviewed and could not be approved.' . $reasonText,
                    'is_read' => false,
                ]);

                try {
                    $this->whatsAppService->sendAccountRejectionNotification($user);
                } catch (\Throwable $e) {
                    Log::warning("WhatsApp rejection notice failed: " . $e->getMessage());
                }
            }
        }

        if ($status === 'active' && $notificationResult) {
            $email = $notificationResult['email'] ?? null;
            if ($email && $email['success']) {
                return back()->with('success', 'Restaurant approved and approval email sent to ' . $email['email'] . '.');
            } elseif ($email && !$email['success'] && $email['attempted']) {
                return back()->with('warning', 'Restaurant approved. Note: Email notification failed (' . $email['message'] . ').');
            }
        }

        return back()->with('success', 'Restaurant status updated to ' . ucfirst($status));
    }

    /**
     * Manage NGOs.
     */
    public function ngos()
    {
        $ngos = NGO::with('user')->latest()->paginate(15);
        return view('admin.ngos', compact('ngos'));
    }

    /**
     * Approve or Reject NGO.
     */
    public function approveNGO(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,pending,rejected',
            'rejection_reason' => 'nullable|string|max:1000',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        $ngo = NGO::with('user')->findOrFail($id);
        $status = $request->status;
        $wasNotActive = ($ngo->status !== 'active');
        $user = $ngo->user;
        $userWasNotActive = $user ? ($user->status !== 'active') : false;

        DB::transaction(function () use ($ngo, $user, $status, $request) {
            $updateData = ['status' => $status];
            if ($request->filled('rejection_reason')) {
                $updateData['rejection_reason'] = $request->rejection_reason;
            }
            if ($request->filled('verification_notes')) {
                $updateData['verification_notes'] = $request->verification_notes;
            }
            $ngo->update($updateData);

            if ($user) {
                $user->update(['status' => $status]);
            }
        });

        $notificationResult = null;
        if ($user) {
            if ($status === 'active' && ($wasNotActive || $userWasNotActive)) {
                $notificationResult = $this->sendApprovalNotification($user);
            } elseif ($status === 'rejected') {
                $reasonText = $request->rejection_reason ? " Reason: {$request->rejection_reason}" : " Please contact support.";
                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Registration Rejected',
                    'message' => 'Your NGO registration for FoodWasteRescue was reviewed and could not be approved.' . $reasonText,
                    'is_read' => false,
                ]);

                try {
                    $this->whatsAppService->sendAccountRejectionNotification($user);
                } catch (\Throwable $e) {
                    Log::warning("WhatsApp rejection notice failed: " . $e->getMessage());
                }
            }
        }

        if ($status === 'active' && $notificationResult) {
            $email = $notificationResult['email'] ?? null;
            if ($email && $email['success']) {
                return back()->with('success', 'NGO approved and approval email sent to ' . $email['email'] . '.');
            } elseif ($email && !$email['success'] && $email['attempted']) {
                return back()->with('warning', 'NGO approved. Note: Email notification failed (' . $email['message'] . ').');
            }
        }

        return back()->with('success', 'NGO status updated to ' . ucfirst($status));
    }

    /**
     * Securely view partner verification documents.
     */
    public function viewDocument(string $type, string $id)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized access to confidential partner verification documents.');
        }

        $path = null;
        if ($type === 'restaurant') {
            $model = Restaurant::findOrFail($id);
            $path = $model->license_document;
        } elseif ($type === 'ngo') {
            $model = NGO::findOrFail($id);
            $path = $model->registration_document;
        } else {
            abort(404, 'Invalid document type.');
        }

        if (!$path) {
            abort(404, 'No document uploaded.');
        }

        $disk = null;
        if (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        } else {
            abort(404, 'Document file not found on server.');
        }

        $mimeType = Storage::disk($disk)->mimeType($path) ?? 'application/pdf';
        return response(Storage::disk($disk)->get($path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Helper to send approval notification avoiding duplicates.
     *
     * @param User|int $user
     * @return array|null
     */
    private function sendApprovalNotification($user): ?array
    {
        if (!($user instanceof User)) {
            $user = User::find($user);
        }

        if (!$user || !in_array($user->role, ['restaurant', 'ngo'])) {
            return null;
        }

        // 1. In-app database notification
        Notification::firstOrCreate(
            [
                'user_id' => $user->id,
                'title' => 'Registration Approved',
            ],
            [
                'message' => 'Good news! Your FoodWasteRescue account has been approved by the admin. You can now log in to your account and use the platform.',
                'is_read' => false,
            ]
        );

        // 2. Email notification
        $recipientEmail = $user->email ?: ($user->restaurant?->email ?: $user->ngo?->email);
        $emailResult = [
            'attempted' => false,
            'success' => false,
            'email' => $recipientEmail,
            'message' => '',
        ];

        if (!empty($recipientEmail)) {
            $emailResult['attempted'] = true;
            try {
                Mail::to($recipientEmail)->send(new AccountApprovedMail($user));
                $emailResult['success'] = true;
                $emailResult['message'] = "Approval email sent successfully to {$recipientEmail}.";
                Log::info("Account approval email successfully sent to {$recipientEmail} for user #{$user->id}");
            } catch (\Throwable $e) {
                $emailResult['success'] = false;
                $emailResult['message'] = "Failed to send email: " . $e->getMessage();
                Log::error("Failed to send account approval email to user #{$user->id} ({$recipientEmail}): " . $e->getMessage());
            }
        } else {
            $emailResult['message'] = "User has no registered email address.";
            Log::warning("Account approval email skipped: user #{$user->id} has no registered email address.");
        }

        // 3. WhatsApp notification (preserves existing integration)
        $whatsAppResult = null;
        if (isset($this->whatsAppService)) {
            try {
                $whatsAppResult = $this->whatsAppService->sendAccountApprovalNotification($user);
            } catch (\Throwable $e) {
                Log::warning("WhatsApp notification dispatch exception: " . $e->getMessage());
            }
        }

        return [
            'email' => $emailResult,
            'whatsapp' => $whatsAppResult,
        ];
    }

    /**
     * Manage Food Donations.
     */
    public function donations()
    {
        $donations = FoodDonation::with('restaurant')->latest()->paginate(15);
        return view('admin.donations', compact('donations'));
    }

    /**
     * Manage Pickup Requests.
     */
    public function requests()
    {
        $requests = PickupRequest::with(['ngo', 'foodDonation.restaurant'])->latest()->paginate(15);
        return view('admin.requests', compact('requests'));
    }

    /**
     * Manage Contact Messages.
     */
    public function contacts()
    {
        $contacts = Contact::latest()->paginate(15);
        return view('admin.contacts', compact('contacts'));
    }

    /**
     * Reply to Contact Message.
     */
    public function replyContact(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);
        
        $request->validate([
            'reply_message' => 'required|string',
        ]);

        $replyMessage = $request->reply_message;

        try {
            Mail::to($contact->email)->send(new ContactReplyMail($replyMessage));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email. Please check mail configuration.');
        }

        $contact->update([
            'status' => 'replied',
            'reply_message' => $replyMessage,
            'replied_at' => now(),
            'replied_by' => auth()->id(),
        ]);

        return back()->with('success', 'Message marked as replied and email sent.');
    }

    /**
     * Reports Overview Page.
     */
    public function reports()
    {
        $donationStats = [
            'total' => FoodDonation::count(),
            'available' => FoodDonation::where('status', 'available')->count(),
            'completed' => FoodDonation::whereIn('status', ['picked', 'completed'])->count(),
            'expired' => FoodDonation::where('status', 'expired')->count(),
        ];

        $restaurantCount = Restaurant::count();
        $ngoCount = NGO::count();

        return view('admin.reports', compact('donationStats', 'restaurantCount', 'ngoCount'));
    }

    /**
     * Export PDF Report.
     */
    public function exportPDF()
    {
        $donations = FoodDonation::with('restaurant')->latest()->get();
        $pdf = Pdf::loadView('admin.reports.pdf', compact('donations'));
        return $pdf->download('donations_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Excel/CSV Report.
     */
    public function exportExcel()
    {
        return Excel::download(new DonationsExport, 'donations_report_' . date('Y-m-d') . '.xlsx');
    }
}
