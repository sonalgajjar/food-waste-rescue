<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications Enabled
    |--------------------------------------------------------------------------
    |
    | Global toggle to enable or disable WhatsApp notifications.
    |
    */
    'enabled' => env('WHATSAPP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "meta_cloud", "twilio"
    |
    */
    'provider' => env('WHATSAPP_PROVIDER', 'meta_cloud'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Platform (Meta Cloud API) Configuration
    |--------------------------------------------------------------------------
    |
    | Meta Cloud API credentials obtained from Meta Developer Dashboard:
    | https://developers.facebook.com/apps/
    |
    */
    'meta' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', env('WHATSAPP_TOKEN', '')),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', env('WHATSAPP_PHONE_ID', '')),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'template_name' => env('WHATSAPP_TEMPLATE_NAME', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio WhatsApp Configuration (Optional Alternative)
    |--------------------------------------------------------------------------
    |
    | Credentials for Twilio WhatsApp API.
    |
    */
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
        'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
        'from_number' => env('TWILIO_WHATSAPP_NUMBER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | Default country calling code without '+' or '0' (e.g. '91' for India).
    | Used if the user registered with a 10-digit number without country code.
    |
    */
    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '91'),

    /*
    |--------------------------------------------------------------------------
    | Approval Notification Message
    |--------------------------------------------------------------------------
    |
    | The message sent to users via WhatsApp when their account is approved.
    |
    */
    'approval_message' => env(
        'WHATSAPP_APPROVAL_MESSAGE',
        'Congratulations! Your FoodWasteRescue account has been approved by the admin. You can now log in to your account and start using FoodWasteRescue. Thank you for joining FoodWasteRescue!'
    ),
];
