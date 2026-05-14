<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account</title>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #0B0F19; color: #E5E7EB; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #121826; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .header { padding: 40px; text-align: center; background: linear-gradient(135deg, #7C3AED 0%, #4F46E5 100%); }
        .logo { height: 48px; width: 48px; margin-bottom: 16px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3)); }
        .content { padding: 48px; text-align: center; }
        h1 { font-size: 28px; font-weight: 800; color: #FFFFFF; margin: 0 0 16px 0; letter-spacing: -0.025em; }
        p { font-size: 16px; line-height: 1.6; color: #9CA3AF; margin-bottom: 32px; }
        .btn { display: inline-block; padding: 16px 36px; background: #7C3AED; color: #FFFFFF; text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 16px; transition: all 0.2s; box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.3); }
        .footer { padding: 32px; text-align: center; font-size: 12px; color: #4B5563; border-top: 1px solid rgba(255,255,255,0.05); }
        .link { color: #7C3AED; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="FluxMedia Logo" class="logo">
            <div style="font-size: 20px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.05em;">FLUXMEDIA STUDIO</div>
        </div>
        <div class="content">
            <h1>Complete Your Onboarding</h1>
            <p>Welcome to the creative suite. To unlock full access to our high-performance media conversion engines and advanced QR matrix profiles, please confirm your identity below.</p>
            <a href="{{ $url }}" class="btn">Verify Account Access</a>
            <p style="margin-top: 32px; font-size: 14px;">If you did not initiate this registration, you can safely ignore this security transmission.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} FluxMedia Studio · Creative Intelligence Platform<br>
            If the button above doesn't work, copy and paste this link:<br>
            <a href="{{ $url }}" class="link">{{ $url }}</a>
        </div>
    </div>
</body>
</html>
