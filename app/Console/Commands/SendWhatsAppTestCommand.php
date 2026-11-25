<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendWhatsAppTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test 
                            {phone? : Recipient mobile number to test sending}
                            {--message= : Custom text message to send}
                            {--user= : User ID to test sending account approval notification}
                            {--check : Only check configuration without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp Cloud API integration, configuration status, or send test approval notification';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsAppService): int
    {
        $this->info("==========================================");
        $this->info("   RentNest WhatsApp Notification System   ");
        $this->info("==========================================");

        $provider = config('services.whatsapp.provider', config('whatsapp.provider', 'meta_cloud'));
        $enabled = config('services.whatsapp.enabled', config('whatsapp.enabled', true));

        $this->line("Status:    " . ($enabled ? '<info>ENABLED</info>' : '<comment>DISABLED</comment>'));
        $this->line("Provider:  <info>{$provider}</info>");

        if ($provider === 'meta_cloud') {
            $token = $whatsAppService->getMetaToken();
            $phoneId = $whatsAppService->getMetaPhoneNumberId();
            $version = config('services.whatsapp.api_version', config('whatsapp.meta.api_version', 'v21.0'));

            $maskedToken = !empty($token) ? substr($token, 0, 6) . '...' . substr($token, -4) : '<fg=red>Not configured</>';
            $maskedPhoneId = !empty($phoneId) ? $phoneId : '<fg=red>Not configured</>';

            $this->line("Phone ID:  {$maskedPhoneId}");
            $this->line("Token:     {$maskedToken}");
            $this->line("Version:   {$version}");
        } else {
            $sid = config('whatsapp.twilio.account_sid');
            $from = config('whatsapp.twilio.from_number');
            $this->line("Twilio SID:  " . (!empty($sid) ? $sid : '<fg=red>Not configured</>'));
            $this->line("Twilio From: " . (!empty($from) ? $from : '<fg=red>Not configured</>'));
        }

        $errors = $whatsAppService->getConfigurationErrors();
        if (!empty($errors)) {
            $this->newLine();
            $this->error("CONFIGURATION WARNING:");
            foreach ($errors as $error) {
                $this->warn(" - {$error}");
            }
        } else {
            $this->newLine();
            $this->info("Configuration: <info>VALID</info>");
        }

        if ($this->option('check')) {
            return empty($errors) ? self::SUCCESS : self::FAILURE;
        }

        // Test with user ID if provided
        $userId = $this->option('user');
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User #{$userId} not found.");
                return self::FAILURE;
            }

            $this->info("Sending approval notification for User: {$user->name} (#{$user->id}) to phone '{$user->phone}'...");
            $result = $whatsAppService->sendAccountApprovalNotification($user);

            if ($result['success']) {
                $this->info("SUCCESS: WhatsApp approval notification sent successfully!");
                $this->line("Recipient: {$result['phone']}");
                if (isset($result['message_id'])) {
                    $this->line("Message ID: {$result['message_id']}");
                }
                return self::SUCCESS;
            } else {
                $this->error("FAILED: Could not send WhatsApp approval notification.");
                $this->warn("Reason: " . $result['message']);
                return self::FAILURE;
            }
        }

        // Test with direct phone number
        $phone = $this->argument('phone');
        if (!$phone) {
            if (!empty($errors)) {
                $this->warn("\nTo send a test WhatsApp message, specify a phone number:");
                $this->line("  php artisan whatsapp:test 9664685349\n");
                return self::FAILURE;
            }
            $phone = $this->ask('Enter destination WhatsApp mobile number (e.g. 9664685349)');
        }

        if (empty($phone)) {
            $this->error("Phone number is required to send a test message.");
            return self::FAILURE;
        }

        $message = $this->option('message') ?: config(
            'whatsapp.approval_message',
            'Congratulations! Your RentNest account has been approved by the admin. You can now log in to your account and start using RentNest. Thank you for joining RentNest!'
        );

        $this->info("Sending test message to '{$phone}'...");
        $result = $whatsAppService->sendTextMessage($phone, $message);

        if ($result['success']) {
            $this->info("SUCCESS: WhatsApp message delivered successfully!");
            $this->line("Recipient: {$result['phone']}");
            if (isset($result['message_id'])) {
                $this->line("Message ID: {$result['message_id']}");
            }
            return self::SUCCESS;
        } else {
            $this->error("FAILED: WhatsApp message was not sent.");
            $this->warn("Reason: " . $result['message']);
            return self::FAILURE;
        }
    }
}
