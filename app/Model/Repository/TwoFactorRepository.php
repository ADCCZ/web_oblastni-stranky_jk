<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;

final class TwoFactorRepository
{
    private const CODE_EXPIRY_MINUTES = 10;

    public function __construct(
        private Explorer $database,
    ) {
    }

    /**
     * Vytvoří 2FA kód pro uživatele
     */
    public function createCode(int $userId, string $type): string
    {
        // Zneplatni staré kódy
        $this->invalidateUserCodes($userId, $type);

        // Vygeneruj 6-místný kód
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->database->table('two_factor_codes')->insert([
            'user_id' => $userId,
            'code' => $code,
            'type' => $type,
            'expires_at' => new \DateTime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'),
        ]);

        return $code;
    }

    /**
     * Ověří 2FA kód
     */
    public function verifyCode(int $userId, string $code, string $type): bool
    {
        $record = $this->database->table('two_factor_codes')
            ->where('user_id', $userId)
            ->where('code', $code)
            ->where('type', $type)
            ->where('expires_at > ?', new \DateTime())
            ->where('used_at IS NULL')
            ->fetch();

        if ($record) {
            $this->database->table('two_factor_codes')
                ->where('id', $record->id)
                ->update(['used_at' => new \DateTime()]);
            return true;
        }

        return false;
    }

    /**
     * Zneplatní kódy uživatele daného typu
     */
    public function invalidateUserCodes(int $userId, string $type): void
    {
        $this->database->table('two_factor_codes')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('used_at IS NULL')
            ->update(['used_at' => new \DateTime()]);
    }

    /**
     * Vyčistí staré záznamy
     */
    public function cleanupExpired(): int
    {
        return $this->database->table('two_factor_codes')
            ->where('expires_at < ?', new \DateTime('-1 day'))
            ->delete();
    }
}
