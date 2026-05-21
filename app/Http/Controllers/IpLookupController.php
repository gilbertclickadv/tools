<?php

namespace App\Http\Controllers;

use App\Models\IpLookup;
use App\Services\IpLookupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IpLookupController extends Controller
{
    /**
     * Display the public IP Lookup dashboard.
     */
    public function index(Request $request, IpLookupService $ipLookupService)
    {
        $ip = $request->ip();

        // For local development or empty IPs, use a public fallback IP to display nice, real-looking data
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            $ip = '8.8.8.8';
        }

        $ipData = $ipLookupService->lookup($ip);

        return Inertia::render('IpLookup', [
            'userIp' => $request->ip(),
            'initialIpData' => $ipData,
        ]);
    }

    /**
     * Look up geolocation, network, and currency data for a given IP.
     */
    public function lookup(Request $request, IpLookupService $ipLookupService)
    {
        $validated = $request->validate([
            'ip' => ['nullable', 'string', 'max:45'],
        ]);

        $ip = trim($validated['ip'] ?? '');

        if (empty($ip)) {
            $ip = $request->ip();
        }

        // Validate IP format if it's not a placeholder/empty
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json([
                'message' => 'Please enter a valid IPv4 or IPv6 address.'
            ], 422);
        }

        // Run lookup
        $result = $ipLookupService->lookup($ip);

        // Track lookup for analytics
        try {
            IpLookup::create([
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'searched_ip' => $ip,
                'country_code' => $result['country_code'] ?? null,
                'country_name' => $result['country_name'] ?? null,
                'city_name' => $result['city_name'] ?? null,
                'asn' => $result['asn'] ? (string) $result['asn'] : null,
                'isp' => $result['isp'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to log IP lookup: " . $e->getMessage());
        }

        return response()->json($result);
    }
}
