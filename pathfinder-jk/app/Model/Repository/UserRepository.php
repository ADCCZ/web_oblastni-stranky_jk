<?php

declare(strict_types=1);

namespace App\Model\Repository;

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
}
