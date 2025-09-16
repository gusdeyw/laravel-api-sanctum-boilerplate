<?php

namespace App\Console\Commands;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-welcome 
                            {email? : Email address to send test to (optional)}
                            {--create-user : Create a new test user instead of using existing}
                            {--force : Skip confirmation prompts}
                            {--name=Test User : Name for the test user}
                            {--phone=+1234567890 : Phone number for the test user}
                            {--address=123 Test Street, Test City : Address for the test user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test welcome email to verify email configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing Welcome Email System...');
        $this->newLine();

        // Get email address
        $email = $this->argument('email') ?: $this->ask('Enter email address to send test to');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email address format');
            return Command::FAILURE;
        }

        try {
            // Create or find user
            if ($this->option('create-user')) {
                $user = User::factory()->create([
                    'name' => $this->option('name'),
                    'email' => $email,
                    'phone' => $this->option('phone'),
                    'address' => $this->option('address'),
                ]);
                $this->info("✅ Created new test user: {$user->name}");
            } else {
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $shouldCreate = $this->confirm("User with email {$email} not found. Create a new test user?");

                    if ($shouldCreate) {
                        $name = $this->ask('Enter user name', 'Test User');
                        $phone = $this->ask('Enter phone number (optional)', '+1234567890');
                        $address = $this->ask('Enter address (optional)', '123 Test Street, Test City');

                        $user = User::factory()->create([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'address' => $address,
                        ]);
                        $this->info("✅ Created new user: {$user->name}");
                    } else {
                        $this->error('❌ Cannot send email without a user');
                        return Command::FAILURE;
                    }
                } else {
                    $this->info("✅ Found existing user: {$user->name}");
                }
            }

            // Display user information
            $this->table(['Field', 'Value'], [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Phone', $user->phone ?: 'Not provided'],
                ['Address', $user->address ?: 'Not provided'],
                ['Created', $user->created_at->format('Y-m-d H:i:s')],
            ]);

            // Confirm sending
            if (!$this->option('force') && !$this->confirm("Send welcome email to {$user->email}?")) {
                $this->info('❌ Email sending cancelled');
                return Command::SUCCESS;
            }

            // Send the email
            $this->info('📧 Sending welcome email...');

            Mail::to($user)->send(new WelcomeEmail($user));

            $this->newLine();
            $this->info('🎉 Welcome email sent successfully!');
            $this->info("📬 Check {$user->email} for the welcome email");

            // Display email configuration
            $this->newLine();
            $this->info('📋 Email Configuration Used:');
            $this->table(['Setting', 'Value'], [
                ['Mailer', config('mail.default')],
                ['Host', config('mail.mailers.smtp.host')],
                ['Port', config('mail.mailers.smtp.port')],
                ['Username', config('mail.mailers.smtp.username')],
                ['Encryption', config('mail.mailers.smtp.encryption') ?: 'None'],
                ['From Address', config('mail.from.address')],
                ['From Name', config('mail.from.name')],
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: ' . $e->getMessage());
            $this->newLine();
            $this->warn('💡 Troubleshooting tips:');
            $this->line('1. Check your .env file email configuration');
            $this->line('2. Verify Gmail App Password (not regular password)');
            $this->line('3. Ensure 2FA is enabled on Gmail account');
            $this->line('4. Check if "Less secure app access" is disabled (good)');
            $this->line('5. Try using php artisan config:clear');

            return Command::FAILURE;
        }
    }
}
