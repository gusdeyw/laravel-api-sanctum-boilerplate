<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .welcome-message {
            font-size: 24px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .user-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .user-info h3 {
            margin-top: 0;
            color: #495057;
        }

        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        .features {
            margin: 20px 0;
        }

        .features ul {
            list-style-type: none;
            padding: 0;
        }

        .features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .features li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <p>API Platform</p>
        </div>

        <div class="welcome-message">
            Welcome aboard, {{ $user->name }}! 🎉
        </div>

        <p>Thank you for registering with {{ config('app.name') }}. We're excited to have you as part of our community!
        </p>

        <div class="user-info">
            <h3>Your Account Details:</h3>
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            @if($user->phone)
            <p><strong>Phone:</strong> {{ $user->phone }}</p>
            @endif
            @if($user->address)
            <p><strong>Address:</strong> {{ $user->address }}</p>
            @endif
            <p><strong>Registration Date:</strong> {{ $user->created_at->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

        <div class="footer">
            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
            <p>
                This email was sent to {{ $user->email }} because you registered for an account on
                {{ config('app.name') }}.
                <br>
                If you didn't create this account, please ignore this email.
            </p>
        </div>
    </div>
</body>

</html>