<?php

declare(strict_types=1);

namespace App\Model\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Json;

final class FormTemplateRepository
{
    public function __construct(
        private Explorer $database,
    ) {
    }

    // ===== Sablony =====

    public function findAll(): Selection
    {
        return $this->database->table('form_templates')
            ->order('created_at DESC');
    }

    public function findActive(): Selection
    {
        return $this->database->table('form_templates')
            ->where('is_active', true)
            ->order('name ASC');
    }

    /**
     * Sablony vytvorene danym uzivatelem + sablony od adminu (sdilene)
     */
    public function findActiveForUser(int $userId): Selection
    {
        // Vlastni sablony + sablony od adminu
        $adminIds = $this->database->table('users')
            ->where('role', 'admin')
            ->fetchPairs(null, 'id');

        $ownerIds = array_unique(array_merge([$userId], $adminIds));

        return $this->database->table('form_templates')
            ->where('is_active', true)
            ->where('created_by', $ownerIds)
            ->order('name ASC');
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('form_templates')
            ->get($id);
    }

    public function create(array $data): ActiveRow
    {
        return $this->database->table('form_templates')
            ->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('form_templates')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('form_templates')
            ->where('id', $id)
            ->update(['is_active' => false]);
    }

    // ===== Pole =====

    public function findFieldsByTemplate(int $templateId): Selection
    {
        return $this->database->table('form_fields')
            ->where('template_id', $templateId)
            ->order('sort_order ASC');
    }

    public function findFieldById(int $id): ?ActiveRow
    {
        return $this->database->table('form_fields')
            ->get($id);
    }

    /**
     * Nahrazeni vsech poli sablony novymi. Zachovava existujici pole (matchuje podle dbId)
     * aby se neztratily FK vazby v registration_responses.
     *
     * @param array $fields Pole s klici: dbId, field_type, label, placeholder, is_required, options, conditions, sort_order
     */
    public function replaceFields(int $templateId, array $fields): void
    {
        $this->database->beginTransaction();

        try {
            // Ziskej aktualni pole
            $existingIds = $this->database->table('form_fields')
                ->where('template_id', $templateId)
                ->fetchPairs('id', 'id');

            $keepIds = [];

            foreach ($fields as $index => $field) {
                $fieldData = [
                    'template_id' => $templateId,
                    'field_type' => $field['field_type'],
                    'label' => $field['label'],
                    'placeholder' => $field['placeholder'] ?? null,
                    'is_required' => $field['is_required'] ?? false,
                    'options' => isset($field['options']) && !empty($field['options'])
                        ? (is_string($field['options']) ? $field['options'] : Json::encode($field['options']))
                        : null,
                    'conditions' => isset($field['conditions']) && !empty($field['conditions'])
                        ? (is_string($field['conditions']) ? $field['conditions'] : Json::encode($field['conditions']))
                        : null,
                    'system_key' => $field['system_key'] ?? null,
                    'sort_order' => $index,
                ];

                $dbId = $field['dbId'] ?? null;

                if ($dbId && isset($existingIds[$dbId])) {
                    // Aktualizuj existujici pole
                    $this->database->table('form_fields')
                        ->where('id', $dbId)
                        ->update($fieldData);
                    $keepIds[] = $dbId;
                } else {
                    // Vytvor nove pole
                    $this->database->table('form_fields')
                        ->insert($fieldData);
                }
            }

            // Smaz pole, ktera uz v nove sade nejsou
            $deleteIds = array_diff(array_values($existingIds), $keepIds);
            if (!empty($deleteIds)) {
                $this->database->table('form_fields')
                    ->where('id', $deleteIds)
                    ->delete();
            }

            $this->database->commit();
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    /**
     * Vraci sablonu i se vsemi poli
     */
    public function getTemplateWithFields(int $templateId): ?array
    {
        $template = $this->findById($templateId);
        if (!$template) {
            return null;
        }

        $fields = $this->findFieldsByTemplate($templateId)->fetchAll();

        return [
            'template' => $template,
            'fields' => $fields,
        ];
    }

    /**
     * Pocet akci pouzivajicich sablonu
     */
    public function getEventCount(int $templateId): int
    {
        return $this->database->table('events')
            ->where('form_template_id', $templateId)
            ->count();
    }

    /**
     * Pocet poli v sablone
     */
    public function getFieldCount(int $templateId): int
    {
        return $this->database->table('form_fields')
            ->where('template_id', $templateId)
            ->count();
    }
}
