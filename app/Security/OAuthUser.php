<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Data transfer object for normalized OAuth user data
 */
final class OAuthUser
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerId,
        public readonly string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
    ) {
    }

    public function getFullName(): string
    {
        $parts = array_filter([$this->firstName, $this->lastName]);
        return implode(' ', $parts) ?: $this->email;
    }
}
