<?php

namespace App\Services\Cart;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * The visitor's cart identity: a random token in an encrypted (EncryptCookies), httpOnly, SameSite=Lax cookie.
 * The cookie is only issued when something is first added, so visitors who never add get none.
 */
class CartToken
{
    public const COOKIE = 'cart_token';

    public const LENGTH = 40;

    private const LIFETIME_MINUTES = 60 * 24 * 30;

    public function current(): ?string
    {
        $token = app(Request::class)->cookie(self::COOKIE);

        return is_string($token) && preg_match('/^[A-Za-z0-9]{'.self::LENGTH.'}$/', $token) === 1 ? $token : null;
    }

    /**
     * The existing token, or a new one that is queued as a cookie. A new token is also set on the current
     * request so the rest of this request (e.g. the shared cart count) already sees it.
     */
    public function issue(): string
    {
        $existing = $this->current();

        if ($existing !== null) {
            return $existing;
        }

        $token = Str::random(self::LENGTH);

        Cookie::queue(Cookie::make(
            self::COOKIE,
            $token,
            self::LIFETIME_MINUTES,
            '/',
            config('session.domain'),
            (bool) config('session.secure'),
            true,
            false,
            'lax',
        ));
        app(Request::class)->cookies->set(self::COOKIE, $token);

        return $token;
    }
}
