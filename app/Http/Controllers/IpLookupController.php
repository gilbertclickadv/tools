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
    public function index(Request $request)
    {
        // Prioritize Cloudflare proxy headers for exact connecting IP
        $userIp = $request->header('CF-Connecting-IP', $request->header('X-Forwarded-For', $request->ip()));
        
        // If multiple IPs are forwarded, extract the first one
        if (is_string($userIp) && str_contains($userIp, ',')) {
            $userIp = explode(',', $userIp)[0];
        }

        return Inertia::render('IpLookup', [
            'userIp' => trim($userIp),
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
            // Prioritize Cloudflare proxy headers
            $ip = $request->header('CF-Connecting-IP', $request->header('X-Forwarded-For', $request->ip()));
            if (is_string($ip) && str_contains($ip, ',')) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
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
            $loggingIp = $request->header('CF-Connecting-IP', $request->ip());
            if (is_string($loggingIp) && str_contains($loggingIp, ',')) {
                $loggingIp = explode(',', $loggingIp)[0];
            }

            IpLookup::create([
                'user_id' => auth()->id(),
                'ip_address' => trim($loggingIp),
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
