<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PickupMessage;
use App\Models\PickupRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Authorize user access to the pickup chat room.
     */
    protected function authorizeChatAccess(PickupRequest $pickupRequest)
    {
        $user = auth()->user();

        $isRestaurant = ($user->role === 'restaurant' && $user->restaurant?->id === $pickupRequest->foodDonation->restaurant_id);
        $isNgo = ($user->role === 'ngo' && $user->ngo?->id === $pickupRequest->ngo_id);
        $isAdmin = ($user->role === 'admin');

        if (!$isRestaurant && !$isNgo && !$isAdmin) {
            abort(403, 'Unauthorized: You are not a participant in this pickup request.');
        }
    }

    /**
     * View Chat Room / Modal.
     */
    public function show($pickupRequestId)
    {
        $pickupRequest = PickupRequest::with([
            'foodDonation.restaurant.user',
            'ngo.user',
        ])->findOrFail($pickupRequestId);

        $this->authorizeChatAccess($pickupRequest);

        // Mark incoming messages as read
        PickupMessage::where('pickup_request_id', $pickupRequest->id)
            ->where('user_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = PickupMessage::with('user')
            ->where('pickup_request_id', $pickupRequest->id)
            ->oldest()
            ->get();

        $isClosed = in_array($pickupRequest->status, ['completed', 'rejected', 'cancelled']);

        return view('chat.room', compact('pickupRequest', 'messages', 'isClosed'));
    }

    /**
     * Send a new message.
     */
    public function send(Request $request, $pickupRequestId)
    {
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant.user', 'ngo.user'])->findOrFail($pickupRequestId);
        $this->authorizeChatAccess($pickupRequest);

        if (in_array($pickupRequest->status, ['completed', 'rejected', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'This pickup request is closed. No further messages can be sent.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $sender = auth()->user();

        $message = PickupMessage::create([
            'pickup_request_id' => $pickupRequest->id,
            'user_id' => $sender->id,
            'message' => trim($validated['message']),
            'is_read' => false,
        ]);

        // Send In-app notification to the counterpart
        $recipientUser = null;
        if ($sender->role === 'restaurant' && $pickupRequest->ngo?->user_id) {
            $recipientUser = $pickupRequest->ngo->user;
        } elseif ($sender->role === 'ngo' && $pickupRequest->foodDonation?->restaurant?->user_id) {
            $recipientUser = $pickupRequest->foodDonation->restaurant->user;
        }

        if ($recipientUser && $recipientUser->id !== $sender->id) {
            Notification::create([
                'user_id' => $recipientUser->id,
                'type' => 'chat_message',
                'title' => 'New Pickup Chat Message',
                'message' => "{$sender->name}: " . \Illuminate\Support\Str::limit($message->message, 80),
                'link' => route('chat.show', $pickupRequest->id),
                'food_donation_id' => $pickupRequest->food_donation_id,
                'is_read' => false,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'user_name' => $sender->name,
                    'user_role' => ucfirst($sender->role),
                    'message' => $message->message,
                    'created_at' => $message->created_at->format('h:i A'),
                    'is_me' => true,
                ],
            ]);
        }

        return back()->with('success', 'Message sent.');
    }

    /**
     * Poll for new messages (real-time fallback).
     */
    public function poll(Request $request, $pickupRequestId)
    {
        $pickupRequest = PickupRequest::with(['foodDonation.restaurant', 'ngo'])->findOrFail($pickupRequestId);
        $this->authorizeChatAccess($pickupRequest);

        $lastId = (int) $request->input('last_id', 0);
        $currentUserId = auth()->id();

        // Mark incoming messages as read
        PickupMessage::where('pickup_request_id', $pickupRequest->id)
            ->where('user_id', '!=', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $newMessages = PickupMessage::with('user')
            ->where('pickup_request_id', $pickupRequest->id)
            ->where('id', '>', $lastId)
            ->oldest()
            ->get()
            ->map(function ($msg) use ($currentUserId) {
                return [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'user_name' => $msg->user?->name ?? 'User',
                    'user_role' => ucfirst($msg->user?->role ?? 'User'),
                    'message' => $msg->message,
                    'created_at' => $msg->created_at->format('h:i A'),
                    'is_me' => ($msg->user_id === $currentUserId),
                ];
            });

        return response()->json([
            'messages' => $newMessages,
            'is_closed' => in_array($pickupRequest->status, ['completed', 'rejected', 'cancelled']),
        ]);
    }
}
