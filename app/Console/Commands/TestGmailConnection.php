<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Swift_TransportException;

class TestGmailConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-connection 
                            {--send-test : Actually send a test email}
                            {--to= : Email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Gmail SMTP connection and credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Gmail SMTP Connection...');
        $this->newLine();

        // Display current configuration
        $this->displayConfiguration();

        // Test SMTP connection
        $this->testSmtpConnection();

        // Optionally send test email
        if ($this->option('send-test')) {
            $this->sendTestEmail();
        } else {
            $this->newLine();
            $this->info('💡 To actually send a test email, use:');
            $this->line('php artisan email:test-connection --send-test --to=your-email@gmail.com');
        }
    }

    private function displayConfiguration()
    {
        $this->info('📋 Current Email Configuration:');
        $this->table(['Setting', 'Value', 'Status'], [
            ['Mailer', config('mail.default'), $this->getStatus(config('mail.default') === 'smtp')],
            ['Host', config('mail.mailers.smtp.host'), $this->getStatus(config('mail.mailers.smtp.host') === 'smtp.gmail.com')],
            ['Port', config('mail.mailers.smtp.port'), $this->getStatus(config('mail.mailers.smtp.port') == 587)],
            ['Username', config('mail.mailers.smtp.username'), $this->getStatus(!empty(config('mail.mailers.smtp.username')))],
            ['Password', $this->maskPassword(config('mail.mailers.smtp.password')), $this->getStatus(!empty(config('mail.mailers.smtp.password')))],
            ['Encryption', config('mail.mailers.smtp.encryption'), $this->getStatus(config('mail.mailers.smtp.encryption') === 'tls')],
            ['From Address', config('mail.from.address'), $this->getStatus(!empty(config('mail.from.address')))],
            ['From Name', config('mail.from.name'), $this->getStatus(!empty(config('mail.from.name')))],
        ]);
        $this->newLine();
    }

    private function testSmtpConnection()
    {
        $this->info('🔌 Testing SMTP Connection...');

        try {
            // Simple connection test by sending a test message
            Mail::raw('Connection test', function ($message) {
                $message->to('test@example.com')
                    ->subject('Connection Test - Do Not Deliver');
            });

            $this->info('✅ SMTP Connection: SUCCESS');
            $this->line('   • Connected to ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));
            $this->line('   • Authentication successful');
            $this->line('   • Configuration appears correct');
        } catch (\Exception $e) {
            $this->error('❌ SMTP Connection: FAILED');
            $this->line('   Error: ' . $e->getMessage());
            $this->newLine();
            $this->provideTroubleshooting($e);
        }

        $this->newLine();
    }

    private function sendTestEmail()
    {
        $email = $this->option('to') ?: $this->ask('Enter email address to send test to');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email address');
            return;
        }

        $this->info("📧 Sending test email to {$email}...");

        try {
            Mail::raw('This is a test email from Laravel API. If you received this, your Gmail SMTP configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email - Laravel API Gmail Configuration');
            });

            $this->info('✅ Test email sent successfully!');
            $this->info("📬 Check {$email} for the test email");
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email: ' . $e->getMessage());
            $this->provideTroubleshooting($e);
        }
    }

    private function getStatus($condition)
    {
        return $condition ? '✅ OK' : '❌ Issue';
    }

    private function maskPassword($password)
    {
        if (empty($password)) {
            return 'Not Set';
        }
        return str_repeat('*', 12) . ' (' . strlen($password) . ' chars)';
    }

    private function provideTroubleshooting(\Exception $e)
    {
        $this->newLine();
        $this->warn('💡 Troubleshooting Tips:');

        $message = strtolower($e->getMessage());

        if (strpos($message, 'authentication') !== false || strpos($message, 'username') !== false) {
            $this->line('🔐 Authentication Issue:');
            $this->line('   • Use Gmail App Password, not regular password');
            $this->line('   • Enable 2-Factor Authentication first');
            $this->line('   • Generate App Password: Google Account → Security → App passwords');
        }

        if (strpos($message, 'connection') !== false || strpos($message, 'timeout') !== false) {
            $this->line('🌐 Connection Issue:');
            $this->line('   • Check internet connection');
            $this->line('   • Verify firewall settings');
            $this->line('   • Try different network if behind corporate firewall');
        }

        if (strpos($message, 'tls') !== false || strpos($message, 'ssl') !== false) {
            $this->line('🔒 Encryption Issue:');
            $this->line('   • Ensure MAIL_ENCRYPTION=tls in .env');
            $this->line('   • Check OpenSSL installation');
        }

        $this->newLine();
        $this->line('🔧 General Steps:');
        $this->line('1. Run: php artisan config:clear');
        $this->line('2. Verify .env file email settings');
        $this->line('3. Test with online SMTP checker (see below)');
        $this->line('4. Check Gmail account security settings');
    }
}
