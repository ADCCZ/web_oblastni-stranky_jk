<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class ChildRepository
{
    public function __construct(
        private Explorer $database,
    ) {
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('children')->get($id);
    }

    public function findByParent(int $parentId): Selection
    {
        return $this->database->table('children')
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->order('first_name, last_name');
    }

    public function create(array $data): ActiveRow
    {
        return $this->database->table('children')->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('children')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('children')
            ->where('id', $id)
            ->update(['is_active' => false]);
    }

    /**
     * Overi, ze dite patri danemu rodici
     */
    public function belongsToParent(int $childId, int $parentId): bool
    {
        return (bool) $this->database->table('children')
            ->where('id', $childId)
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->fetch();
    }
}
