<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate a dynamic XML sitemap with all public, canonical routes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): Response
    {
        // Define all public, canonical paths that should be crawled and indexed by Google
        $urls = [
            '', // Home page
            '/tools/image',
            '/tools/qr-code',
            '/tools/ip-lookup',
            '/tools/base64',
            '/tools/json-formatter',
            '/tools/hash-generator',
            '/tools/color-picker',
            '/tools/text',
            '/tools/jwt',
            '/tools/regex',
            '/tools/csv-json',
            '/tools/svg-architect',
            '/tools/url-shortener',
            '/tools/uuid-generator',
            '/tools/password-generator',
            '/tools/ai-background-remover',
            '/privacy',
            '/terms',
        ];

        // Start XML string
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' .
                ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' .
                ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
        
        foreach ($urls as $url) {
            $xml .= '    <url>' . "\n";
            $xml .= '        <loc>' . htmlspecialchars(url($url), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            $xml .= '        <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '        <changefreq>weekly</changefreq>' . "\n";
            $xml .= '        <priority>' . ($url === '' ? '1.0' : '0.8') . '</priority>' . "\n";
            $xml .= '    </url>' . "\n";
        }
        
        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'text/xml');
    }
}
