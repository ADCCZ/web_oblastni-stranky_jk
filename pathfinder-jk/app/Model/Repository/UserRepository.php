<?php

declare(strict_types=1);

namespace App\Model\Repository;

use App\Security\OAuthUser;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class UserRepository
{
    public function __construct(
        private Explorer $database,
    ) {
    }

    public function findByEmail(string $email): ?ActiveRow
    {
        return $this->database->table('users')
            ->where('email', $email)
            ->fetch();
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('users')
            ->get($id);
    }

    public function create(array $data): ActiveRow
    {
        return $this->database->table('users')
            ->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('users')
            ->where('id', $id)
            ->update($data);
    }

    public function emailExists(string $email): bool
    {
        return $this->database->table('users')
            ->where('email', $email)
            ->count() > 0;
    }

    public function getAll(): array
    {
        return $this->database->table('users')
            ->order('last_name, first_name')
            ->fetchAll();
    }

    /**
     * Find user by OAuth provider ID
     */
    public function findByOAuthProvider(string $provider, string $providerId): ?ActiveRow
    {
        $column = match ($provider) {
            'google' => 'google_id',
            'facebook' => 'facebook_id',
            'discord' => 'discord_id',
            default => null,
        };

        if ($column === null) {
            return null;
        }

        return $this->database->table('users')
            ->where($column, $providerId)
            ->fetch();
    }

    /**
     * Link OAuth account to existing user
     */
    public function linkOAuthAccount(int $userId, string $provider, string $providerId): void
    {
        $column = match ($provider) {
            'google' => 'google_id',
            'facebook' => 'facebook_id',
            'discord' => 'discord_id',
            default => null,
        };

        if ($column === null) {
            return;
        }

        $this->update($userId, [$column => $providerId]);
    }

    /**
     * Create new user from OAuth data
     */
    public function createFromOAuth(OAuthUser $oauthUser): ActiveRow
    {
        $column = match ($oauthUser->provider) {
            'google' => 'google_id',
            'facebook' => 'facebook_id',
            'discord' => 'discord_id',
            default => throw new \InvalidArgumentException("Unknown provider: {$oauthUser->provider}"),
        };

        $data = [
            'email' => $oauthUser->email,
            'first_name' => $oauthUser->firstName ?? 'Uživatel',
            'last_name' => $oauthUser->lastName ?? '',
            'role' => 'member',
            'is_active' => true,
            $column => $oauthUser->providerId,
            'password_hash' => null, // OAuth users don't have password
        ];

        return $this->create($data);
    }
}
