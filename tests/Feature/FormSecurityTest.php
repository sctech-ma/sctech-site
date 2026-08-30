<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\FormSecurityGate;
use SCTech\Services\FormTokenService;
use SCTech\Tests\Feature\Support\MemorySessionStore;
use SCTech\Tests\Feature\Support\MutableClock;
use SCTech\Tests\Feature\Support\StubRateLimiter;
use DateTimeImmutable;
use DateTimeZone;

final class FormSecurityTest extends TestCase
{
    public function testValidTokensPassAfterMinimumAgeAndCsrfIsSingleUse(): void
    {
        $clock = new MutableClock(new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC')));
        $session = new MemorySessionStore();
        $csrf = new CsrfTokenManager($session);
        $tokens = new FormTokenService(str_repeat('t', 32), $clock);
        $gate = new FormSecurityGate($csrf, $tokens, new StubRateLimiter(true));
        $csrfToken = $csrf->issue('contact');
        $timing = $tokens->issueTimingToken('contact');
        $clock->advance('+3 seconds');

        $result = $gate->check('contact', $csrfToken, $timing, $tokens->issueIdempotencyKey(), '', hash('sha256', 'ip'));
        self::assertTrue($result->validation->isValid());

        $replay = $gate->check('contact', $csrfToken, $timing, $tokens->issueIdempotencyKey(), '', hash('sha256', 'ip'));
        self::assertFalse($replay->validation->isValid());
    }

    public function testHoneypotFastSubmissionExpiredTokenAndRateLimitAreRejected(): void
    {
        $clock = new MutableClock(new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC')));
        $session = new MemorySessionStore();
        $csrf = new CsrfTokenManager($session);
        $tokens = new FormTokenService(str_repeat('t', 32), $clock);
        $timing = $tokens->issueTimingToken('quote');

        $fast = (new FormSecurityGate($csrf, $tokens, new StubRateLimiter(true)))->check(
            'quote',
            $csrf->issue('quote'),
            $timing,
            $tokens->issueIdempotencyKey(),
            '',
            hash('sha256', 'ip'),
        );
        self::assertFalse($fast->validation->isValid());

        $clock->advance('+3 seconds');
        $bot = (new FormSecurityGate($csrf, $tokens, new StubRateLimiter(true)))->check(
            'quote',
            $csrf->issue('quote'),
            $timing,
            $tokens->issueIdempotencyKey(),
            'https://spam.invalid',
            hash('sha256', 'ip'),
        );
        self::assertFalse($bot->validation->isValid());

        $limited = (new FormSecurityGate($csrf, $tokens, new StubRateLimiter(false, 600)))->check(
            'quote',
            $csrf->issue('quote'),
            $timing,
            $tokens->issueIdempotencyKey(),
            '',
            hash('sha256', 'ip'),
        );
        self::assertFalse($limited->validation->isValid());
        self::assertSame(600, $limited->retryAfter);

        $expiredTiming = $tokens->issueTimingToken('quote');
        $clock->advance('+7201 seconds');
        $expired = (new FormSecurityGate($csrf, $tokens, new StubRateLimiter(true)))->check(
            'quote',
            $csrf->issue('quote'),
            $expiredTiming,
            $tokens->issueIdempotencyKey(),
            '',
            hash('sha256', 'ip'),
        );
        self::assertFalse($expired->validation->isValid());
    }
}
