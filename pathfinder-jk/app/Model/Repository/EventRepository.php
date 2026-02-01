<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class EventRepository
{
    public function __construct(
        private Explorer $database,
    ) {
    }

    public function findAll(): Selection
    {
        return $this->database->table('events')
            ->order('start_date DESC');
    }

    public function findPublished(): Selection
    {
        return $this->database->table('events')
            ->where('is_published', true)
            ->order('start_date DESC');
    }

    public function findUpcoming(int $limit = 6): Selection
    {
        return $this->database->table('events')
            ->where('is_published', true)
            ->where('start_date >= ?', new \DateTime())
            ->order('start_date ASC')
            ->limit($limit);
    }

    public function findPast(int $limit = 10): Selection
    {
        return $this->database->table('events')
            ->where('is_published', true)
            ->where('start_date < ?', new \DateTime())
            ->order('start_date DESC')
            ->limit($limit);
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('events')
            ->get($id);
    }

    public function findBySlug(string $slug): ?ActiveRow
    {
        return $this->database->table('events')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->fetch();
    }

    public function findByCategory(int $categoryId): Selection
    {
        return $this->database->table('events')
            ->where('category_id', $categoryId)
            ->where('is_published', true)
            ->order('start_date DESC');
    }

    public function create(array $data): ActiveRow
    {
        return $this->database->table('events')
            ->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('events')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('events')
            ->where('id', $id)
            ->delete();
    }

    public function getRegistrationCount(int $eventId): int
    {
        return $this->database->table('registrations')
            ->where('event_id', $eventId)
            ->where('status', ['pending', 'confirmed'])
            ->count();
    }
}
