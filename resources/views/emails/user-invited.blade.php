<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You've been invited to {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 30px; border-radius: 10px;">
        <h1 style="color: #2d3748; margin-bottom: 20px;">Welcome to {{ config('app.name') }}!</h1>

        <p style="margin-bottom: 15px;">Hello {{ $user->name }},</p>

        <p style="margin-bottom: 15px;">You have been invited to join {{ config('app.name') }}. An account has been created for you with the following credentials:</p>

        <div style="background-color: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #3b82f6;">
            <p style="margin: 0 0 10px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 0;"><strong>Password:</strong> {{ $password }}</p>
        </div>

        <p style="margin-bottom: 15px;">Please use the button below to log in:</p>

        <p style="text-align: center; margin: 25px 0;">
            <a href="{{ route('login') }}" style="background-color: #3b82f6; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Log In Now</a>
        </p>

        <p style="margin-bottom: 15px; color: #e53e3e;"><strong>Important:</strong> For security reasons, we recommend changing your password after your first login.</p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;">

        <p style="color: #718096; font-size: 14px; margin: 0;">
            If you did not expect this invitation, please ignore this email or contact support.
        </p>
    </div>
</body>
</html>
