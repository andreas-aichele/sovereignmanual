<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const string COOKIE = 'locale';

    private const int COOKIE_MINUTES = 2_628_000;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->preferredLocale($request);

        App::setLocale($locale);
        Cookie::queue(self::COOKIE, $locale, self::COOKIE_MINUTES);

        return $next($request);
    }

    private function preferredLocale(Request $request): string
    {
        $routeLocale = $request->route('locale');

        if (is_string($routeLocale) && Locales::isSupported($routeLocale)) {
            return $routeLocale;
        }

        if ($this->isUnlocalizedMagazineRoute($request)) {
            return Locales::fallback();
        }

        $cookieLocale = $request->cookie(self::COOKIE);

        if (is_string($cookieLocale) && Locales::isSupported($cookieLocale)) {
            return $cookieLocale;
        }

        $browserLocale = $request->getPreferredLanguage(Locales::supported());

        if (is_string($browserLocale) && Locales::isSupported($browserLocale)) {
            return $browserLocale;
        }

        return Locales::fallback();
    }

    private function isUnlocalizedMagazineRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return is_string($routeName)
            && in_array($routeName, ['magazine.index', 'magazine.about', 'magazine.category', 'magazine.show'], true);
    }
}
