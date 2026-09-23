<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class RegistrationRepository
{
    public function __construct(
        private Explorer $database,
    ) {
    }

    // ===== Registrace =====

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('registrations')
            ->get($id);
    }

    public function findByEventId(int $eventId): Selection
    {
        return $this->database->table('registrations')
            ->where('event_id', $eventId)
            ->order('created_at DESC');
    }

    public function findByUserAndEvent(int $userId, int $eventId): ?ActiveRow
    {
        return $this->database->table('registrations')
            ->where('user_id', $userId)
            ->where('event_id', $eventId)
            ->fetch();
    }

    public function create(array $data): ActiveRow
    {
        return $this->database->table('registrations')
            ->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('registrations')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('registrations')
            ->where('id', $id)
            ->delete();
    }

    public function isUserRegistered(int $userId, int $eventId): bool
    {
        return (bool) $this->database->table('registrations')
            ->where('user_id', $userId)
            ->where('event_id', $eventId)
            ->where('status != ?', 'cancelled')
            ->fetch();
    }

    public function getCountByEvent(int $eventId): int
    {
        return $this->database->table('registrations')
            ->where('event_id', $eventId)
            ->where('status', ['pending', 'confirmed'])
            ->count();
    }

    // ===== Odpovedi =====

    /**
     * Ulozeni odpovedi na vsechna pole formulare
     *
     * @param array $responses [field_id => value, ...]
     */
    public function createResponses(int $registrationId, array $responses): void
    {
        foreach ($responses as $fieldId => $value) {
            $this->database->table('registration_responses')
                ->insert([
                    'registration_id' => $registrationId,
                    'field_id' => (int) $fieldId,
                    'value' => $value !== '' ? (string) $value : null,
                ]);
        }
    }

    public function findResponsesByRegistration(int $registrationId): Selection
    {
        return $this->database->table('registration_responses')
            ->where('registration_id', $registrationId);
    }

    /**
     * Vsechny registrace pro akci vcetne uzivatelu a odpovedi (pro Excel export)
     *
     * @return array Pole registraci s klici: first_name, last_name, email, phone, status, note, created_at, responses
     */
    public function findResponsesByEvent(int $eventId): array
    {
        $registrations = $this->database->table('registrations')
            ->where('event_id', $eventId)
            ->order('created_at ASC')
            ->fetchAll();

        $result = [];

        foreach ($registrations as $reg) {
            $responses = $this->database->table('registration_responses')
                ->where('registration_id', $reg->id)
                ->fetchPairs('field_id', 'value');

            // Zakladni udaje primo z registrace (nebo fallback na users)
            $firstName = $reg->first_name;
            $lastName = $reg->last_name;

            if (!$firstName) {
                $user = $this->database->table('users')->get($reg->user_id);
                $firstName = $user ? $user->first_name : '';
                $lastName = $user ? $user->last_name : '';
            }

            $result[] = [
                'id' => $reg->id,
                'user_id' => $reg->user_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_pathfinder_member' => (bool) $reg->is_pathfinder_member,
                'street' => $reg->street,
                'city' => $reg->city,
                'zip' => $reg->zip,
                'birth_date' => $reg->birth_date,
                'status' => $reg->status,
                'note' => $reg->note,
                'created_at' => $reg->created_at,
                'responses' => $responses,
            ];
        }

        return $result;
    }
}
