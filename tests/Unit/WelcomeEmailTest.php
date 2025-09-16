<?php

namespace Tests\Unit;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_can_be_sent(): void
    {
        // Fake the Mail facade to capture sent emails
        Mail::fake();

        // Create a test user
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, Country'
        ]);

        // Send the welcome email (will be queued since WelcomeEmail implements ShouldQueue)
        Mail::to($user)->send(new WelcomeEmail($user));

        // Assert that the email was queued
        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        // Assert that exactly one email was queued
        Mail::assertQueued(WelcomeEmail::class, 1);
    }

    public function test_welcome_email_has_correct_subject(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $mailable = new WelcomeEmail($user);

        $envelope = $mailable->envelope();

        $this->assertEquals('Welcome to ' . config('app.name') . '!', $envelope->subject);
    }

    public function test_welcome_email_uses_correct_view(): void
    {
        $user = User::factory()->create();
        $mailable = new WelcomeEmail($user);

        $content = $mailable->content();

        $this->assertEquals('emails.welcome', $content->view);
    }

    public function test_welcome_email_contains_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+1987654321',
            'address' => '456 Oak Street'
        ]);

        $mailable = new WelcomeEmail($user);

        // Test that the mailable has the correct user
        $this->assertEquals($user->id, $mailable->user->id);
        $this->assertEquals('Jane Smith', $mailable->user->name);
        $this->assertEquals('jane@example.com', $mailable->user->email);
    }

    public function test_welcome_email_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'phone' => '+1122334455',
            'address' => '789 Pine Avenue'
        ]);

        $mailable = new WelcomeEmail($user);

        // Render the email to HTML
        $html = $mailable->render();

        // Assert that the rendered email contains user information
        $this->assertStringContainsString('Bob Johnson', $html);
        $this->assertStringContainsString('bob@example.com', $html);
        $this->assertStringContainsString('+1122334455', $html);
        $this->assertStringContainsString('789 Pine Avenue', $html);
        $this->assertStringContainsString('Welcome aboard', $html);
    }

    public function test_welcome_email_handles_optional_fields(): void
    {
        // Test with user that has no phone or address
        $user = User::factory()->create([
            'name' => 'Alice Wilson',
            'email' => 'alice@example.com',
            'phone' => null,
            'address' => null
        ]);

        $mailable = new WelcomeEmail($user);
        $html = $mailable->render();

        // Should contain required fields
        $this->assertStringContainsString('Alice Wilson', $html);
        $this->assertStringContainsString('alice@example.com', $html);

        // Should handle null phone and address gracefully
        $this->assertStringNotContainsString('Phone:', $html);
        $this->assertStringNotContainsString('Address:', $html);
    }

    public function test_multiple_welcome_emails_can_be_sent(): void
    {
        Mail::fake();

        // Create multiple users
        $users = User::factory()->count(3)->create();

        // Queue welcome emails to all users
        foreach ($users as $user) {
            Mail::to($user)->queue(new WelcomeEmail($user));
        }

        // Assert that 3 emails were queued
        Mail::assertQueued(WelcomeEmail::class, 3);

        // Assert each user received their email
        foreach ($users as $user) {
            Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
                return $mail->user->id === $user->id;
            });
        }
    }

    public function test_welcome_email_is_queued(): void
    {
        $user = User::factory()->create();
        $mailable = new WelcomeEmail($user);

        // Assert that the mailable implements ShouldQueue
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }
}
