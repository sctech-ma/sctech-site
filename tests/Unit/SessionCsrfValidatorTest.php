<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SCTech\Core\Csrf;
use SCTech\Core\Session;
use SCTech\Core\Validator;

final class SessionCsrfValidatorTest extends TestCase
{
    public function testCsrfTokensAreStrongStableAndRotatable(): void
    {
        $session = Session::memory();
        $csrf = new Csrf($session);

        $token = $csrf->token();
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
        self::assertSame($token, $csrf->token());
        self::assertTrue($csrf->verify($token));
        self::assertFalse($csrf->verify($token . 'x'));
        self::assertNotSame($token, $csrf->rotate());
    }

    public function testFlashDataLivesForExactlyTheFollowingRequest(): void
    {
        $session = Session::memory();
        $session->flash('notice', 'Message reçu');
        $session->commit();

        self::assertSame('Message reçu', $session->get('notice'));
        $session->commit();

        self::assertNull($session->get('notice'));
    }

    public function testValidatorHandlesUnicodeLengthsWhitelistsAndConfirmation(): void
    {
        $validator = Validator::make([
            'name' => 'Élodie',
            'email' => 'elodie@example.test',
            'subject' => 'cloud',
            'password' => 'très-secret',
            'password_confirmation' => 'très-secret',
        ], [
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:254',
            'subject' => 'required|in:data,cloud,cybersecurite',
            'password' => 'required|min:10|confirmed',
        ]);

        self::assertTrue($validator->passes());
        self::assertSame('Élodie', $validator->validated()['name']);
    }

    public function testValidatedDataCannotBeReadAfterFailure(): void
    {
        $validator = Validator::make(['email' => 'not-an-email'], ['email' => 'required|email']);
        self::assertTrue($validator->fails());
        self::assertNotNull($validator->first('email'));

        $this->expectException(InvalidArgumentException::class);
        $validator->validated();
    }
}
