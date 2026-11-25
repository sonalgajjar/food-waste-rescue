<?php

namespace App\Console\Commands;

use App\Mail\AccountApprovedMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : The recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test approval notification email to verify SMTP delivery to mobile/inbox';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->argument('email');
        $mailer = config('mail.default');

        $this->info("Current Mail Driver: [{$mailer}]");

        if ($mailer === 'log') {
            $this->warn("NOTICE: MAIL_MAILER is currently set to 'log'.");
            $this->warn("Emails will be written to storage/logs/laravel.log instead of reaching real mobile inboxes.");
            $this->warn("To receive real emails on your phone, set MAIL_MAILER=smtp and configure Gmail/SMTP in .env.");
        }

        $user = new User([
            'name' => 'Test User',
            'email' => $recipient,
            'role' => 'restaurant',
            'status' => 'active',
        ]);

        $this->info("Attempting to send approval email to {$recipient}...");

        try {
            Mail::to($recipient)->send(new AccountApprovedMail($user));
            $this->info("SUCCESS: Approval notification sent successfully!");
            if ($mailer === 'log') {
                $this->line("Check the output in storage/logs/laravel.log.");
            } else {
                $this->line("Please check your mobile phone's email app / Gmail inbox now.");
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("ERROR: Failed to send email.");
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
