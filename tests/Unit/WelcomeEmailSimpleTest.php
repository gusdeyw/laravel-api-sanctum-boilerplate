<?php

namespace Tests\Unit;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Tests\TestCase;

class WelcomeEmailSimpleTest extends TestCase
{
    public function test_welcome_email_mailable_structure(): void
    {
        // Create a user instance without saving to database
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, Country',
            'created_at' => now(),
        ]);

        $mailable = new WelcomeEmail($user);

        // Test envelope
        $envelope = $mailable->envelope();
        $this->assertEquals('Welcome to ' . config('app.name') . '!', $envelope->subject);

        // Test content
        $content = $mailable->content();
        $this->assertEquals('emails.welcome', $content->view);

        // Test that user is accessible
        $this->assertEquals($user->name, $mailable->user->name);
        $this->assertEquals($user->email, $mailable->user->email);
    }

    public function test_welcome_email_implements_should_queue(): void
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        $mailable = new WelcomeEmail($user);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }
}
