<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-test-mail {email} {--html : Send a beautifully designed HTML email instead of plain text}')]
#[Description('Send a test email to verify Brevo SMTP configurations')]
class SendTestMail extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $sendHtml = $this->option('html');

        $this->info("Attempting to send a test email to {$email} using Brevo SMTP...");

        try {
            if ($sendHtml) {
                // A beautiful, modern glassmorphic styled HTML email that matches premium design aesthetics
                $appName = config('app.name', 'Midas Image Platform');
                $year = date('Y');
                
                $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brevo SMTP Test Mail</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0f172a;
            font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #f1f5f9;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 2px;
            border-radius: 24px;
            background: linear-gradient(135deg, #6366f1, #3b82f6, #ec4899);
        }
        .inner-card {
            background-color: rgba(15, 23, 42, 0.95);
            border-radius: 22px;
            padding: 48px 40px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(to right, #818cf8, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 24px;
            letter-spacing: -0.025em;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 9999px;
            color: #818cf8;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 32px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 16px 0;
            letter-spacing: -0.025em;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            color: #94a3b8;
            margin: 0 0 32px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            border-radius: 12px;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(148, 163, 184, 0.15), transparent);
            margin: 32px 0;
        }
        .footer {
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .footer a {
            color: #94a3b8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="inner-card">
            <div class="logo">{$appName}</div>
            <div class="badge">Connection Successful</div>
            <h1>Brevo SMTP is fully operational!</h1>
            <p>This is a premium, beautifully crafted HTML verification mail sent from your Laravel application using the Brevo SMTP relay integration.</p>
            <a href="#" class="button">Go to Dashboard</a>
            <div class="divider"></div>
            <div class="footer">
                &copy; {$year} {$appName}. All rights reserved.<br>
                Sent via <a href="https://www.brevo.com" target="_blank">Brevo SMTP Relay</a>.
            </div>
        </div>
    </div>
</body>
</html>
HTML;

                Mail::html($htmlContent, function ($message) use ($email, $appName) {
                    $message->to($email)
                        ->subject("✨ {$appName} - Brevo SMTP Test Successful!");
                });
            } else {
                Mail::raw("Hello! This is a test email sent from Midas Image Platform via Brevo SMTP. Connection is working flawlessly!", function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Brevo SMTP Connection Test');
                });
            }

            $this->info("✅ Test email sent successfully to {$email}!");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email. Error: " . $e->getMessage());
            return 1;
        }
    }
}
