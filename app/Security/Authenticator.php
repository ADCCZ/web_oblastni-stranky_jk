<?php

declare(strict_types=1);

namespace App\Security;

use App\Model\Repository\UserRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator as NetteAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

final class Authenticator implements NetteAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function authenticate(string $username, string $password): IIdentity
    {
        $user = $this->userRepository->findByEmail($username);

        if (!$user) {
            throw new AuthenticationException('Uživatel s tímto e-mailem neexistuje.', self::IDENTITY_NOT_FOUND);
        }

        if (!$user->is_active) {
            throw new AuthenticationException('Účet byl deaktivován.', self::NOT_APPROVED);
        }

        if (!password_verify($password, $user->password_hash)) {
            throw new AuthenticationException('Nesprávné heslo.', self::INVALID_CREDENTIAL);
        }

        return new SimpleIdentity(
            $user->id,
            $user->role,
            [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
            ],
        );
    }
}
