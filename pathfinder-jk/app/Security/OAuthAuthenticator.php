<?php

declare(strict_types=1);

namespace App\Security;

use App\Model\Repository\UserRepository;
use Nette\Security\SimpleIdentity;
use Nette\Security\IIdentity;

/**
 * Authenticator for OAuth login flow
 */
final class OAuthAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Authenticate user via OAuth data
     * - If user with provider ID exists, log them in
     * - If user with same email exists, link OAuth and log them in
     * - Otherwise, create new user
     */
    public function authenticate(OAuthUser $oauthUser): IIdentity
    {
        // 1. Try to find user by OAuth provider ID
        $user = $this->userRepository->findByOAuthProvider(
            $oauthUser->provider,
            $oauthUser->providerId
        );

        if ($user) {
            return $this->createIdentity($user);
        }

        // 2. Try to find user by email and link OAuth account
        if ($oauthUser->email) {
            $user = $this->userRepository->findByEmail($oauthUser->email);

            if ($user) {
                // Link OAuth to existing account
                $this->userRepository->linkOAuthAccount(
                    $user->id,
                    $oauthUser->provider,
                    $oauthUser->providerId
                );

                return $this->createIdentity($user);
            }
        }

        // 3. Create new user from OAuth data
        $user = $this->userRepository->createFromOAuth($oauthUser);

        return $this->createIdentity($user);
    }

    /**
     * Create Nette identity from user row
     */
    private function createIdentity(mixed $user): IIdentity
    {
        return new SimpleIdentity(
            $user->id,
            $user->role,
            [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
            ]
        );
    }
}
