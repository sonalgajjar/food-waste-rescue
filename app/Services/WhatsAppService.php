<?php

namespace App\Services;

use App\Models\FoodDonation;
use App\Models\NGO;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    /**
     * Send an account approval WhatsApp notification to the user.
     */
    public function sendAccountApprovalNotification(User $user): array
    {
        $phone = $user->phone ?: ($user->restaurant?->phone ?: $user->ngo?->phone);

        if (empty($phone)) {
            Log::warning("WhatsApp approval notification skipped: User #{$user->id} ({$user->name}) has no phone number.");
            return [
                'success' => false,
                'status' => 'missing_phone',
                'message' => 'User does not have a registered mobile number.',
                'phone' => null,
            ];
        }

        $messageTemplate = config(
            'services.whatsapp.approval_message',
            config(
                'whatsapp.approval_message',
                'Congratulations! Your FoodWasteRescue account has been approved by the admin. You can now log in to your account and start using FoodWasteRescue. Thank you for joining FoodWasteRescue!'
            )
        );

        $message = str_replace(
            ['{name}', '{role}', '{app_name}', '{appName}'],
            [$user->name, ucfirst((string) $user->role), config('app.name', 'FoodWasteRescue'), config('app.name', 'FoodWasteRescue')],
            $messageTemplate
        );

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send an account rejection WhatsApp notification.
     */
    public function sendAccountRejectionNotification(User $user, ?string $reason = null): array
    {
        $phone = $user->phone ?: ($user->restaurant?->phone ?: $user->ngo?->phone);

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available.'];
        }

        $message = "Hello {$user->name}, your FoodWasteRescue account registration was reviewed and could not be approved at this time" .
            ($reason ? " Reason: {$reason}." : ".") .
            " Please contact support if you believe this is an error.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send registration acknowledgement to new user.
     */
    public function sendNewRegistrationNotification(User $user): array
    {
        $phone = $user->phone ?: ($user->restaurant?->phone ?: $user->ngo?->phone);

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available.'];
        }

        $message = "Welcome to FoodWasteRescue, {$user->name}! Your " . ucfirst((string)$user->role) .
            " account has been registered and is pending administrator verification. We will notify you once approved.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send new pickup request notification to restaurant.
     */
    public function sendNewPickupRequestNotification(PickupRequest $request): array
    {
        $donation = $request->foodDonation;
        $restaurantUser = $donation?->restaurant?->user;
        $phone = $restaurantUser?->phone ?: $donation?->restaurant?->phone;

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available for restaurant.'];
        }

        $ngoName = $request->ngo?->ngo_name ?? 'An NGO partner';
        $timeStr = $request->pickup_time ? $request->pickup_time->format('M d, H:i') : 'Scheduled time';

        $message = "FoodWasteRescue Alert: {$ngoName} requested pickup for \"{$donation->food_name}\" ({$donation->quantity} {$donation->unit}) scheduled for {$timeStr}. Please log in to accept or decline.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send nearby surplus food alert notification to NGO.
     */
    public function sendNearbyFoodAlertNotification(FoodDonation $donation, NGO $ngo, ?float $distanceKm = null): array
    {
        $ngoUser = $ngo->user;
        $phone = $ngoUser?->phone ?: $ngo->phone;

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available for NGO.'];
        }

        $restName = $donation->restaurant?->restaurant_name ?? 'A nearby restaurant';
        $distStr = $distanceKm !== null ? " (~{$distanceKm} km away)" : '';
        $url = url('/ngo/donations/' . $donation->id);

        $message = "FoodRescue Alert: Fresh surplus food posted near you! {$restName} posted \"{$donation->food_name}\" ({$donation->quantity} {$donation->unit}){$distStr}. Check details and claim pickup: {$url}";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send pickup accepted notification to NGO.
     */
    public function sendPickupAcceptedNotification(PickupRequest $request): array
    {
        $ngoUser = $request->ngo?->user;
        $phone = $ngoUser?->phone ?: $request->ngo?->phone;

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available for NGO.'];
        }

        $restName = $request->foodDonation?->restaurant?->restaurant_name ?? 'The donor restaurant';
        $foodName = $request->foodDonation?->food_name ?? 'food item';

        $message = "Great news! {$restName} accepted your pickup request for \"{$foodName}\". Please arrive at the scheduled pickup address on time.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send pickup rejected notification to NGO.
     */
    public function sendPickupRejectedNotification(PickupRequest $request): array
    {
        $ngoUser = $request->ngo?->user;
        $phone = $ngoUser?->phone ?: $request->ngo?->phone;

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available for NGO.'];
        }

        $restName = $request->foodDonation?->restaurant?->restaurant_name ?? 'The donor';
        $foodName = $request->foodDonation?->food_name ?? 'food item';

        $message = "Update from FoodWasteRescue: {$restName} could not accept your pickup request for \"{$foodName}\". Please browse other available food listings in your area.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send pickup completed notification.
     */
    public function sendPickupCompletedNotification(PickupRequest $request): array
    {
        $donation = $request->foodDonation;
        $restaurantUser = $donation?->restaurant?->user;
        $phone = $restaurantUser?->phone ?: $donation?->restaurant?->phone;

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available for donor.'];
        }

        $ngoName = $request->ngo?->ngo_name ?? 'The NGO partner';
        $message = "FoodWasteRescue: {$ngoName} successfully completed redistribution of \"{$donation->food_name}\"! Thank you for saving surplus food from going to waste.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send food expiry alert.
     */
    public function sendFoodExpiryNotification(FoodDonation $donation, User $user): array
    {
        $phone = $user->phone ?: ($user->restaurant?->phone ?: $user->ngo?->phone);

        if (empty($phone)) {
            return ['success' => false, 'status' => 'missing_phone', 'message' => 'No phone number available.'];
        }

        $message = "FoodWasteRescue Notice: Food listing \"{$donation->food_name}\" has reached its expiry time and is now marked unavailable.";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send a freeform text message via configured WhatsApp provider.
     */
    public function sendTextMessage(string $toPhone, string $message): array
    {
        // 1. Check if WhatsApp notifications are enabled
        $enabled = config('services.whatsapp.enabled', config('whatsapp.enabled', true));
        if (!$enabled) {
            Log::info("WhatsApp notification skipped: WhatsApp notifications are disabled in configuration.");
            return [
                'success' => false,
                'status' => 'disabled',
                'message' => 'WhatsApp notifications are disabled in configuration.',
                'phone' => $toPhone,
            ];
        }

        // 2. Validate configuration
        $configErrors = $this->getConfigurationErrors();
        if (!empty($configErrors)) {
            $errorExplanation = implode(' ', $configErrors);
            Log::info("WhatsApp notification skipped: {$errorExplanation}");
            return [
                'success' => false,
                'status' => 'missing_configuration',
                'message' => $errorExplanation,
                'phone' => $toPhone,
            ];
        }

        // 3. Normalize phone number to E.164 without leading +
        $normalizedPhone = $this->normalizePhoneNumber($toPhone);
        if (!$normalizedPhone) {
            Log::warning("WhatsApp notification failed: Invalid phone number '{$toPhone}'.");
            return [
                'success' => false,
                'status' => 'invalid_phone',
                'message' => "The phone number '{$toPhone}' is invalid or could not be formatted.",
                'phone' => $toPhone,
            ];
        }

        $provider = config('services.whatsapp.provider', config('whatsapp.provider', 'meta_cloud'));

        if ($provider === 'twilio') {
            return $this->sendViaTwilio($normalizedPhone, $message);
        }

        return $this->sendViaMetaCloudApi($normalizedPhone, $message);
    }

    /**
     * Get Meta WhatsApp Access Token strictly via config() helper.
     */
    public function getMetaToken(): string
    {
        $token = config('services.whatsapp.access_token') 
            ?: config('whatsapp.meta.access_token');

        return trim((string) $token, "\"' \t\n\r\0\x0B");
    }

    /**
     * Get Meta WhatsApp Phone Number ID strictly via config() helper.
     */
    public function getMetaPhoneNumberId(): string
    {
        $id = config('services.whatsapp.phone_number_id') 
            ?: config('whatsapp.meta.phone_number_id');

        return trim((string) $id, "\"' \t\n\r\0\x0B");
    }

    /**
     * Send message using Meta WhatsApp Business Platform (Cloud API).
     */
    protected function sendViaMetaCloudApi(string $normalizedPhone, string $message): array
    {
        $token = $this->getMetaToken();
        $phoneNumberId = $this->getMetaPhoneNumberId();
        $apiVersion = config('services.whatsapp.api_version', config('whatsapp.meta.api_version', 'v21.0'));

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalizedPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['messages'][0]['id'] ?? 'unknown_id';

                Log::info("WhatsApp message delivered via Meta Cloud API to +{$normalizedPhone}. Message ID: {$messageId}");

                return [
                    'success' => true,
                    'status' => 'delivered',
                    'phone' => '+' . $normalizedPhone,
                    'message_id' => $messageId,
                    'message' => 'WhatsApp notification sent successfully.',
                    'response' => $responseData,
                ];
            }

            $errorData = $response->json('error') ?? [];
            $errorMessage = $errorData['message'] ?? $response->body();
            $errorCode = $errorData['code'] ?? $response->status();

            // Safe logging: redact access token
            $sanitizedErrorMessage = $errorMessage;
            if (!empty($token)) {
                $sanitizedErrorMessage = str_replace($token, '[REDACTED]', $sanitizedErrorMessage);
            }

            Log::error("WhatsApp Cloud API error (Code {$errorCode}) to +{$normalizedPhone}: {$sanitizedErrorMessage}");

            return [
                'success' => false,
                'status' => 'api_error',
                'phone' => '+' . $normalizedPhone,
                'message' => "WhatsApp Cloud API error ({$errorCode}): {$sanitizedErrorMessage}",
                'error_code' => $errorCode,
            ];
        } catch (Throwable $e) {
            $safeExceptionMessage = $e->getMessage();
            if (!empty($token)) {
                $safeExceptionMessage = str_replace($token, '[REDACTED]', $safeExceptionMessage);
            }

            Log::error("WhatsApp Cloud API connection exception for +{$normalizedPhone}: " . $safeExceptionMessage);

            return [
                'success' => false,
                'status' => 'connection_error',
                'phone' => '+' . $normalizedPhone,
                'message' => 'WhatsApp Cloud API connection error: ' . $safeExceptionMessage,
            ];
        }
    }

    /**
     * Send message using Twilio WhatsApp API.
     */
    protected function sendViaTwilio(string $normalizedPhone, string $message): array
    {
        $sid = config('whatsapp.twilio.account_sid');
        $token = config('whatsapp.twilio.auth_token');
        $from = config('whatsapp.twilio.from_number');

        $fromFormatted = str_starts_with($from, 'whatsapp:') ? $from : "whatsapp:{$from}";
        $toFormatted = "whatsapp:+{$normalizedPhone}";

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->timeout(10)
                ->asForm()
                ->post($url, [
                    'From' => $fromFormatted,
                    'To' => $toFormatted,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['sid'] ?? 'unknown_sid';

                Log::info("WhatsApp message delivered via Twilio to +{$normalizedPhone}. SID: {$messageId}");

                return [
                    'success' => true,
                    'status' => 'delivered',
                    'phone' => '+' . $normalizedPhone,
                    'message_id' => $messageId,
                    'message' => 'WhatsApp notification sent successfully.',
                    'response' => $responseData,
                ];
            }

            $errorMsg = $response->json('message') ?? $response->body();
            $errorCode = $response->json('code') ?? $response->status();

            if (!empty($token)) {
                $errorMsg = str_replace($token, '[REDACTED]', $errorMsg);
            }

            Log::error("Twilio WhatsApp API error ({$errorCode}) to +{$normalizedPhone}: {$errorMsg}");

            return [
                'success' => false,
                'status' => 'api_error',
                'phone' => '+' . $normalizedPhone,
                'message' => "Twilio WhatsApp API error ({$errorCode}): {$errorMsg}",
            ];
        } catch (Throwable $e) {
            $safeExceptionMessage = $e->getMessage();
            if (!empty($token)) {
                $safeExceptionMessage = str_replace($token, '[REDACTED]', $safeExceptionMessage);
            }

            Log::error("Twilio WhatsApp connection exception for +{$normalizedPhone}: " . $safeExceptionMessage);

            return [
                'success' => false,
                'status' => 'connection_error',
                'phone' => '+' . $normalizedPhone,
                'message' => 'Twilio WhatsApp connection error: ' . $safeExceptionMessage,
            ];
        }
    }

    /**
     * Check if WhatsApp provider credentials are configured.
     */
    public function isConfigured(): bool
    {
        return empty($this->getConfigurationErrors());
    }

    /**
     * Return list of configuration issues if any.
     */
    public function getConfigurationErrors(): array
    {
        $errors = [];
        $provider = config('services.whatsapp.provider', config('whatsapp.provider', 'meta_cloud'));

        if ($provider === 'twilio') {
            $sid = trim((string) config('whatsapp.twilio.account_sid'));
            $token = trim((string) config('whatsapp.twilio.auth_token'));
            $from = trim((string) config('whatsapp.twilio.from_number'));

            if (empty($sid) || $sid === 'your_twilio_account_sid_here' || str_contains($sid, 'your_')) {
                $errors[] = 'TWILIO_ACCOUNT_SID is missing in .env.';
            }
            if (empty($token) || $token === 'your_twilio_auth_token_here' || str_contains($token, 'your_')) {
                $errors[] = 'TWILIO_AUTH_TOKEN is missing in .env.';
            }
            if (empty($from) || str_contains($from, 'your_')) {
                $errors[] = 'TWILIO_WHATSAPP_NUMBER is missing in .env.';
            }
        } else {
            $token = $this->getMetaToken();
            $phoneId = $this->getMetaPhoneNumberId();

            if (empty($token)) {
                $errors[] = 'WHATSAPP_ACCESS_TOKEN is missing in .env.';
            } elseif ($token === 'your_actual_meta_access_token' || str_contains($token, 'your_') || str_contains($token, 'placeholder')) {
                $errors[] = "WHATSAPP_ACCESS_TOKEN is set to placeholder ('{$token}') in .env.";
            }

            if (empty($phoneId)) {
                $errors[] = 'WHATSAPP_PHONE_NUMBER_ID is missing in .env.';
            } elseif ($phoneId === 'your_actual_phone_number_id' || str_contains($phoneId, 'your_') || str_contains($phoneId, 'placeholder')) {
                $errors[] = "WHATSAPP_PHONE_NUMBER_ID is set to placeholder ('{$phoneId}') in .env.";
            }
        }

        return $errors;
    }

    /**
     * Clean and format phone number into E.164 international digits (without +).
     */
    public function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (empty($digits)) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $countryCode = ltrim(
                config('services.whatsapp.default_country_code', config('whatsapp.default_country_code', '91')),
                '+0'
            );
            $digits = $countryCode . $digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
