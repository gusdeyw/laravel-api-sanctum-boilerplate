<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailSendingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that actually sends a real email (for manual testing)
     * 
     * This test should be run manually when you want to test actual email sending.
     * Make sure your .env is configured with real email credentials.
     * 
     * Run with: php artisan test --filter test_send_real_welcome_email
     */
    public function test_send_real_welcome_email(): void
    {
        // Skip this test if not explicitly running email tests
        if (!env('TEST_SEND_REAL_EMAILS', false)) {
            $this->markTestSkipped('Real email testing is disabled. Set TEST_SEND_REAL_EMAILS=true to enable.');
        }

        // Create a test user with realistic data
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => env('TEST_EMAIL_RECIPIENT', 'test@example.com'),
            'phone' => '+1234567890',
            'address' => '123 Test Street, Test City, Test Country'
        ]);

        // Send the actual email (not faked)
        Mail::to($user)->send(new WelcomeEmail($user));

        // If we get here without exceptions, the email was sent successfully
        $this->assertTrue(true, 'Welcome email sent successfully to ' . $user->email);

        // Log success message
        $this->artisan('tinker', ['--execute' => 'echo "Email sent to: ' . $user->email . '";']);
    }

    /**
     * Test sending email to multiple recipients
     */
    public function test_send_welcome_email_to_multiple_recipients(): void
    {
        if (!env('TEST_SEND_REAL_EMAILS', false)) {
            $this->markTestSkipped('Real email testing is disabled.');
        }

        $recipients = [
            env('TEST_EMAIL_RECIPIENT', 'test@example.com'),
            env('TEST_EMAIL_RECIPIENT_2', 'test2@example.com'),
        ];

        foreach ($recipients as $email) {
            if ($email && $email !== 'test@example.com' && $email !== 'test2@example.com') {
                $user = User::factory()->create([
                    'name' => 'Test User for ' . $email,
                    'email' => $email,
                    'phone' => '+1234567890',
                    'address' => '123 Test Street'
                ]);

                Mail::to($user)->send(new WelcomeEmail($user));
            }
        }

        $this->assertTrue(true, 'Multiple welcome emails sent successfully');
    }

    /**
     * Test the welcome email content and structure
     */
    public function test_welcome_email_content_structure(): void
    {
        $user = User::factory()->create([
            'name' => 'Content Test User',
            'email' => 'content@test.com',
            'phone' => '+1987654321',
            'address' => '456 Content Street, Email City'
        ]);

        $mailable = new WelcomeEmail($user);
        $rendered = $mailable->render();

        // Test that all expected content is present
        $expectedContent = [
            'Welcome aboard, Content Test User',
            'content@test.com',
            '+1987654321',
            '456 Content Street, Email City',
            'What you can do with your account',
            'Access our secure API endpoints',
            'Create and manage blog posts',
            config('app.name')
        ];

        foreach ($expectedContent as $content) {
            $this->assertStringContainsString(
                $content,
                $rendered,
                "Email should contain: {$content}"
            );
        }

        // Test HTML structure
        $this->assertStringContainsString('<html', $rendered);
        $this->assertStringContainsString('<body', $rendered);
        $this->assertStringContainsString('email-container', $rendered);
        $this->assertStringContainsString('welcome-message', $rendered);
    }

    /**
     * Test email with queue (for performance testing)
     */
    public function test_welcome_email_queue_functionality(): void
    {
        // Don't actually send emails in this test
        Mail::fake();

        $user = User::factory()->create();

        // Test that email is queued
        Mail::to($user)->queue(new WelcomeEmail($user));

        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    /**
     * Test welcome email with different user data scenarios
     */
    public function test_welcome_email_with_various_user_data(): void
    {
        $testCases = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'address' => '123 Main St, City, Country'
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => null,
                'address' => '456 Oak Avenue'
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'phone' => '+1987654321',
                'address' => null
            ],
            [
                'name' => 'Alice Wilson',
                'email' => 'alice@example.com',
                'phone' => null,
                'address' => null
            ]
        ];

        foreach ($testCases as $userData) {
            $user = User::factory()->create($userData);
            $mailable = new WelcomeEmail($user);
            $rendered = $mailable->render();

            // Should always contain name and email
            $this->assertStringContainsString($userData['name'], $rendered);
            $this->assertStringContainsString($userData['email'], $rendered);

            // Should conditionally contain phone and address
            if ($userData['phone']) {
                $this->assertStringContainsString($userData['phone'], $rendered);
            }
            if ($userData['address']) {
                $this->assertStringContainsString($userData['address'], $rendered);
            }
        }
    }
}
