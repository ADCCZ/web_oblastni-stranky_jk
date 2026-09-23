<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class PasswordResetRepository
{
    private const CODE_EXPIRY_MINUTES = 15;

    public function __construct(
        private Explorer $database,
    ) {
    }

    /**
     * Vytvoří nový reset kód pro uživatele
     */
    public function createResetCode(int $userId): string
    {
        // Zneplatni staré kódy
        $this->invalidateUserCodes($userId);

        // Vygeneruj 6-místný kód
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->database->table('password_resets')->insert([
            'user_id' => $userId,
            'code' => $code,
            'expires_at' => new \DateTime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'),
        ]);

        return $code;
    }

    /**
     * Ověří reset kód
     */
    public function verifyCode(int $userId, string $code): bool
    {
        $reset = $this->database->table('password_resets')
            ->where('user_id', $userId)
            ->where('code', $code)
            ->where('expires_at > ?', new \DateTime())
            ->where('used_at IS NULL')
            ->fetch();

        return $reset !== null;
    }

    /**
     * Označí kód jako použitý
     */
    public function markCodeAsUsed(int $userId, string $code): void
    {
        $this->database->table('password_resets')
            ->where('user_id', $userId)
            ->where('code', $code)
            ->update(['used_at' => new \DateTime()]);
    }

    /**
     * Zneplatní všechny kódy uživatele
     */
    public function invalidateUserCodes(int $userId): void
    {
        $this->database->table('password_resets')
            ->where('user_id', $userId)
            ->where('used_at IS NULL')
            ->update(['used_at' => new \DateTime()]);
    }

    /**
     * Vyčistí staré záznamy
     */
    public function cleanupExpired(): int
    {
        return $this->database->table('password_resets')
            ->where('expires_at < ?', new \DateTime('-1 day'))
            ->delete();
    }
}
