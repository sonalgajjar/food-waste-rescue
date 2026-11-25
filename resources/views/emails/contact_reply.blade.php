<!DOCTYPE html>
<html>
<head>
    <title>Contact Reply - FoodRescue</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #4CAF50;">Response to Your Inquiry</h2>
        <p>Hello,</p>
        <p>Thank you for contacting FoodRescue. Here is the response to your message:</p>
        <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
            {!! nl2br(e($replyMessage)) !!}
        </div>
        <p>If you have any further questions, feel free to reply to this email or contact us again.</p>
        <p>Best Regards,<br><strong>FoodRescue Team</strong></p>
    </div>
</body>
</html>
