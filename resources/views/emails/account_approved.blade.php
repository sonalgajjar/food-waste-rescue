<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your FoodWasteRescue Account Has Been Approved!</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 20px; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 32px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
        
        <div style="border-bottom: 2px solid #059669; padding-bottom: 16px; margin-bottom: 24px;">
            <h2 style="color: #059669; margin: 0; font-size: 24px; font-weight: 700;">FoodWasteRescue</h2>
        </div>

        <p style="margin-top: 0; font-size: 16px;">Hello {{ $userName }},</p>

        <p style="font-size: 16px;">Good news! Your FoodWasteRescue account has been approved by the admin. You can now log in to your account and use the platform.</p>

        <p style="font-size: 16px;"><strong>Your account type:</strong> {{ $accountType }}</p>

        <div style="margin: 28px 0;">
            <a href="{{ $loginUrl }}" style="background-color: #059669; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: 600; display: inline-block;">Log In to Your Account</a>
        </div>

        <p style="font-size: 14px; color: #64748b;">Or visit: <a href="{{ $loginUrl }}" style="color: #059669; word-break: break-all;">{{ $loginUrl }}</a></p>

        <p style="font-size: 16px;">Thank you for joining FoodWasteRescue!</p>

        <p style="margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 15px; color: #475569;">
            Regards,<br>
            <strong>FoodWasteRescue Team</strong>
        </p>

    </div>
</body>
</html>
