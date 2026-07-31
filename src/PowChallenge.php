<?php

namespace DigraphCMS;

use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use Joby\Smol\PoW\SmolPoW;
use Joby\Smol\Sentry\Severity;

class PowChallenge
{
    protected static SmolPoW|null $pow;

    public static function require(): void
    {
        $status = static::checkCookie();
        // if cookie is valid we're done
        if ($status)
            return;
        // if cookie is expired, log and bounce
        if ($status === false)
            Context::sentry()->signal('pow_challenge_fail', Severity::Suspicious);
        throw new ArbitraryRedirectException(static::challengeUrl(Context::url()));
    }

    public static function checkCookie(): bool|null
    {
        return static::pow()->validateCookieValue($_COOKIE['smolpow']);
    }

    public static function challengePage(): string
    {
        return (new File(
            'smolpow.html',
            Templates::render('smolpow.php'),
        ))->url();
    }

    public static function challengeUrl(URL $bounce_after): string
    {
        $challenge_string = static::pow()->challengeString($bounce_after);
        return static::challengePage() . '#' . $challenge_string;
    }

    public static function pow(): SmolPoW
    {
        return static::$pow
            ??= static::defaultPow();
    }

    protected static function defaultPow(): SmolPoW
    {
        return new SmolPoW(
            Config::secret(),
            'sha256',
            Config::get('proofofwork.difficulty') ?? 5,
            Config::get('proofofwork.expiration') ?? (3600 * 8),
            ['sha256'],
        );
    }
}
