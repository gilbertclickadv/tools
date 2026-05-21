<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="index, follow">
        <meta property="og:site_name" content="FluxMedia">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary_large_image">

        <meta name="theme-color" content="#0B0F19">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/webp" href="/assets/images/icon_only.webp">
        <link rel="apple-touch-icon" href="/assets/images/pwa-192.webp">
        <link rel="apple-touch-startup-image" href="/assets/images/fluxmedia_main.webp">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="preload" as="image" href="/assets/images/fluxmedia_main.webp" fetchpriority="high">


        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">


        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <noscript>
            <!-- SEO Fallback Content for Crawlers and Non-JS users -->
            <div style="padding: 20px; max-width: 800px; margin: 0 auto; font-family: system-ui, -apple-system, sans-serif; color: #9ca3af;">
                <h1 style="color: #ffffff;">FluxMedia Free Online Developer Tools</h1>
                <p>Explore our suite of high-performance web developer utilities, network tools, and productivity applications.</p>
                
                @if(isset($page['props']['initialIpData']))
                    @php $ipData = $page['props']['initialIpData']; @endphp
                    <h2 style="color: #ffffff; margin-top: 30px;">IP Address Geolocation Details: {{ $ipData['ip'] }}</h2>
                    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; border-color: #374151; color: #e5e7eb; margin-top: 15px;">
                        <tr style="background-color: #1f2937;">
                            <td><strong>IP Address</strong></td>
                            <td>{{ $ipData['ip'] }} (IPv{{ $ipData['ip_version'] }})</td>
                        </tr>
                        <tr>
                            <td><strong>Country</strong></td>
                            <td>{{ $ipData['country_name'] }} ({{ $ipData['country_code'] }})</td>
                        </tr>
                        <tr style="background-color: #1f2937;">
                            <td><strong>City / Region</strong></td>
                            <td>{{ $ipData['city_name'] }}, {{ $ipData['region_name'] }}</td>
                        </tr>
                        <tr>
                            <td><strong>ISP / Network Provider</strong></td>
                            <td>{{ $ipData['isp'] }} (ASN: AS{{ $ipData['asn'] }})</td>
                        </tr>
                        <tr style="background-color: #1f2937;">
                            <td><strong>Coordinates</strong></td>
                            <td>Latitude: {{ $ipData['latitude'] }}, Longitude: {{ $ipData['longitude'] }}</td>
                        </tr>
                        <tr>
                            <td><strong>Timezone</strong></td>
                            <td>{{ $ipData['timezone'] }}</td>
                        </tr>
                        @if(isset($ipData['currency']))
                            <tr style="background-color: #1f2937;">
                                <td><strong>Local Currency</strong></td>
                                <td>{{ $ipData['currency']['name'] }} ({{ $ipData['currency']['code'] }} - {{ $ipData['currency']['symbol'] }})</td>
                            </tr>
                        @endif
                    </table>
                @endif
                
                <h3 style="color: #ffffff; margin-top: 30px;">Explore Other Free Tools:</h3>
                <ul style="line-height: 1.8;">
                    <li><a href="/tools/qr-code" style="color: #3b82f6;">QR Code Generator & Creator</a></li>
                    <li><a href="/tools/image" style="color: #3b82f6;">Image Studio & Format Converter</a></li>
                    <li><a href="/tools/base64" style="color: #3b82f6;">Base64 Encoder / Decoder</a></li>
                    <li><a href="/tools/json-formatter" style="color: #3b82f6;">JSON Formatter, Validator & Beautifier</a></li>
                    <li><a href="/tools/hash-generator" style="color: #3b82f6;">Cryptographic Hash Generator (MD5, SHA-1, SHA-256)</a></li>
                    <li><a href="/tools/color-picker" style="color: #3b82f6;">CSS Color Picker & Palette Builder</a></li>
                    <li><a href="/tools/text" style="color: #3b82f6;">Online Text Transformation Utility</a></li>
                    <li><a href="/tools/jwt" style="color: #3b82f6;">JWT Debugger & Encoder / Decoder</a></li>
                    <li><a href="/tools/regex" style="color: #3b82f6;">Regular Expression (Regex) Tester</a></li>
                    <li><a href="/tools/csv-json" style="color: #3b82f6;">CSV ⇆ JSON Format Transformer</a></li>
                    <li><a href="/tools/svg-architect" style="color: #3b82f6;">SVG Path Builder & Optimizer</a></li>
                    <li><a href="/tools/url-shortener" style="color: #3b82f6;">High-Speed URL Shortener</a></li>
                    <li><a href="/tools/uuid-generator" style="color: #3b82f6;">RFC 4122 UUID Generator</a></li>
                    <li><a href="/tools/password-generator" style="color: #3b82f6;">Secure Password & Passphrase Generator</a></li>
                </ul>
            </div>
        </noscript>
    </body>
</html>
