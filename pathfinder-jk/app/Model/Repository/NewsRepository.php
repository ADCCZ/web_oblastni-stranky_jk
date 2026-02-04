<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class NewsRepository
{
    public function __construct(
        private Explorer $database,
    ) {
    }

    public function findAll(): Selection
    {
        return $this->database->table('news')
            ->order('published_at DESC');
    }

    public function findPublished(): Selection
    {
        return $this->database->table('news')
            ->where('is_published', true)
            ->where('published_at <= ?', new \DateTime())
            ->order('published_at DESC');
    }

    public function findLatest(int $limit = 6): Selection
    {
        return $this->database->table('news')
            ->where('is_published', true)
            ->where('published_at <= ?', new \DateTime())
            ->order('published_at DESC')
            ->limit($limit);
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('news')
            ->get($id);
    }

    public function findBySlug(string $slug): ?ActiveRow
    {
        return $this->database->table('news')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->fetch();
    }

    public function create(array $data): ActiveRow
    {
        return $this->database->table('news')
            ->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('news')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('news')
            ->where('id', $id)
            ->delete();
    }
}
