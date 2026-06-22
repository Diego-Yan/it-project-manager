<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Supported locales.
     */
    public const SUPPORTED = ['zh_CN', 'en'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->detectLocale($request);

        if (in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Detect the best locale: Session → User → Browser → Config
     */
    private function detectLocale(Request $request): string
    {
        // 1. Session (explicit user choice, may not be logged in)
        if ($locale = Session::get('locale')) {
            return $locale;
        }

        // 2. Authenticated user preference
        if ($user = $request->user()) {
            if ($user->locale && in_array($user->locale, self::SUPPORTED, true)) {
                return $user->locale;
            }
        }

        // 3. Browser Accept-Language header
        if ($acceptLang = $request->header('Accept-Language')) {
            $browserLocale = $this->parseAcceptLanguage($acceptLang);
            if ($browserLocale) {
                return $browserLocale;
            }
        }

        // 4. Config fallback
        return config('app.locale', 'en');
    }

    /**
     * Parse Accept-Language header and return best match.
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        // Map browser locales to our supported ones
        $map = [
            'zh'       => 'zh_CN',
            'zh-cn'    => 'zh_CN',
            'zh-hans'  => 'zh_CN',
            'zh-hans-cn' => 'zh_CN',
            'zh-sg'    => 'zh_CN',
            'en'       => 'en',
            'en-us'    => 'en',
            'en-gb'    => 'en',
        ];

        // Split by comma and sort by quality factor (q=)
        $locales = explode(',', $header);
        $parsed = [];

        foreach ($locales as $locale) {
            $parts = explode(';q=', trim($locale));
            $code = strtolower(trim($parts[0]));
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;
            $parsed[$code] = $quality;
        }

        // Sort by quality descending
        arsort($parsed);

        foreach ($parsed as $code => $q) {
            if (isset($map[$code])) {
                return $map[$code];
            }
            // Try prefix match (e.g., "zh-TW" → check "zh")
            $prefix = strstr($code, '-', true) ?: $code;
            if (isset($map[$prefix])) {
                return $map[$prefix];
            }
        }

        return null;
    }
}
