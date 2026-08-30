<?php

declare(strict_types=1);

namespace SCTech\Services;

final readonly class AuthResult
{
    /** @param array{id:int,role:string,email:string,display_name:string}|null $user */
    public function __construct(
        public bool $authenticated,
        public ?array $user = null,
        public bool $rateLimited = false,
    ) {
    }
}
