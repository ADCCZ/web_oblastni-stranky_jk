<?php

declare(strict_types=1);

namespace App\Presentation\FormTemplate;

use App\Model\Repository\FormTemplateRepository;
use App\Presentation\BasePresenter;
use Nette\Utils\Json;

final class FormTemplatePresenter extends BasePresenter
{
    public function __construct(
        private FormTemplateRepository $formTemplateRepository,
    ) {
    }

    public function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage('Pro správu formulářů se musíte přihlásit.', 'warning');
            $this->redirect('Sign:in');
        }

        if (!$this->getUser()->isInRole('admin') && !$this->getUser()->isInRole('leader')) {
            $this->flashMessage('Nemáte oprávnění ke správě formulářů.', 'error');
            $this->redirect('Home:');
        }
    }

    public function renderDefault(): void
    {
        if ($this->getUser()->isInRole('admin')) {
            $templates = $this->formTemplateRepository->findActive()->fetchAll();
        } else {
            $templates = $this->formTemplateRepository->findActiveForUser($this->getUser()->getId())->fetchAll();
        }

        // Pridej pocty poli a akci ke kazdemu templatovi
        $currentUserId = $this->getUser()->getId();
        $templateData = [];
        foreach ($templates as $t) {
            $templateData[] = [
                'template' => $t,
                'fieldCount' => $this->formTemplateRepository->getFieldCount($t->id),
                'eventCount' => $this->formTemplateRepository->getEventCount($t->id),
                'isOwn' => $t->created_by === $currentUserId,
            ];
        }

        $this->template->templates = $templateData;
    }

    /**
     * Definice presetu sablon
     */
    private const PRESETS = [
        'vikendovka' => [
            'name' => 'Víkendovka',
            'description' => 'Šablona pro víkendové akce',
            'fields' => [
                ['fieldType' => 'text', 'label' => 'Email', 'systemKey' => 'email'],
                ['fieldType' => 'text', 'label' => 'Telefon', 'systemKey' => 'phone'],
                ['fieldType' => 'checkbox', 'label' => 'Vegetarián', 'systemKey' => 'is_vegetarian'],
                ['fieldType' => 'text', 'label' => 'Alergie', 'placeholder' => 'Např. pyly, ořechy, penicilin...', 'systemKey' => 'allergies'],
                ['fieldType' => 'text', 'label' => 'Léky', 'placeholder' => 'Pravidelně užívané léky...', 'systemKey' => 'medications'],
                ['fieldType' => 'text', 'label' => 'Zdravotní požadavky', 'placeholder' => 'Např. astma, diabetes...', 'systemKey' => 'health_notes'],
                ['fieldType' => 'select', 'label' => 'Umí plavat', 'options' => ['Ano', 'Ne'], 'systemKey' => 'can_swim'],
            ],
        ],
        'tabor' => [
            'name' => 'Tábor',
            'description' => 'Šablona pro tábory a delší akce',
            'fields' => [
                ['fieldType' => 'text', 'label' => 'Email', 'systemKey' => 'email'],
                ['fieldType' => 'text', 'label' => 'Telefon', 'systemKey' => 'phone'],
                ['fieldType' => 'checkbox', 'label' => 'Vegetarián', 'systemKey' => 'is_vegetarian'],
                ['fieldType' => 'text', 'label' => 'Stravovací návyky', 'placeholder' => 'Např. bezlepková dieta, veganství...', 'systemKey' => 'dietary_notes'],
                ['fieldType' => 'text', 'label' => 'Alergie', 'placeholder' => 'Např. pyly, ořechy, penicilin...', 'systemKey' => 'allergies'],
                ['fieldType' => 'text', 'label' => 'Léky', 'placeholder' => 'Pravidelně užívané léky...', 'systemKey' => 'medications'],
                ['fieldType' => 'text', 'label' => 'Zdravotní požadavky', 'placeholder' => 'Např. astma, diabetes...', 'systemKey' => 'health_notes'],
                ['fieldType' => 'select', 'label' => 'Umí plavat', 'options' => ['Ano', 'Ne'], 'systemKey' => 'can_swim'],
                ['fieldType' => 'text', 'label' => 'Velikost oblečení', 'placeholder' => 'Např. 140, S, M, L...', 'systemKey' => 'clothing_size'],
            ],
        ],
    ];

    public function renderBuilder(?int $id = null, ?string $preset = null): void
    {
        $templateJson = 'null';

        // Preset pro novou sablonu
        if (!$id && $preset && isset(self::PRESETS[$preset])) {
            $p = self::PRESETS[$preset];
            $fields = [];
            foreach ($p['fields'] as $f) {
                $fields[] = [
                    'fieldType' => $f['fieldType'],
                    'label' => $f['label'],
                    'placeholder' => $f['placeholder'] ?? '',
                    'isRequired' => $f['isRequired'] ?? false,
                    'options' => $f['options'] ?? [],
                    'conditions' => null,
                    'systemKey' => $f['systemKey'] ?? null,
                ];
            }
            $templateJson = Json::encode([
                'name' => $p['name'],
                'description' => $p['description'],
                'fields' => $fields,
            ]);
        }

        if ($id) {
            $data = $this->formTemplateRepository->getTemplateWithFields($id);

            if (!$data || !$data['template']->is_active) {
                $this->flashMessage('Šablona nebyla nalezena.', 'error');
                $this->redirect('FormTemplate:');
            }

            // Leader otevre admin sablonu jako novou kopii (bez id)
            $isOwner = $this->getUser()->isInRole('admin') || $data['template']->created_by === $this->getUser()->getId();

            // Serializace do JSON pro JS
            $fields = [];
            foreach ($data['fields'] as $field) {
                $fields[] = [
                    'dbId' => $field->id,
                    'fieldType' => $field->field_type,
                    'label' => $field->label,
                    'placeholder' => $field->placeholder ?? '',
                    'isRequired' => (bool) $field->is_required,
                    'options' => $field->options ? Json::decode($field->options) : [],
                    'conditions' => $field->conditions ? Json::decode($field->conditions, forceArrays: true) : null,
                    'systemKey' => $field->system_key,
                ];
            }

            $jsonData = [
                'name' => $data['template']->name,
                'description' => $data['template']->description ?? '',
                'fields' => $fields,
            ];

            if ($isOwner) {
                // Vlastni sablona - editace
                $jsonData['id'] = $data['template']->id;
            } else {
                // Admin sablona - kopie (bez id, bez dbId u poli)
                $jsonData['name'] .= ' (kopie)';
                foreach ($jsonData['fields'] as &$f) {
                    unset($f['dbId']);
                }
                unset($f);
            }

            $templateJson = Json::encode($jsonData);
        }

        $this->template->templateJson = $templateJson;
        $this->template->editId = (isset($isOwner) && $isOwner) ? $id : null;
    }

    /**
     * AJAX: Ulozeni sablony (nova nebo editace)
     */
    public function handleSaveTemplate(): void
    {
        $this->getHttpResponse()->setContentType('application/json');

        $rawInput = file_get_contents('php://input');
        if (!$rawInput) {
            $this->sendJson(['success' => false, 'errors' => ['Prázdný požadavek.']]);
        }

        try {
            $data = Json::decode($rawInput, forceArrays: true);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'errors' => ['Neplatný formát dat.']]);
        }

        // Validace
        $errors = [];
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $errors[] = 'Zadejte název šablony.';
        }

        $fields = $data['fields'] ?? [];
        if (empty($fields)) {
            $errors[] = 'Šablona musí obsahovat alespoň jedno pole.';
        }

        $validTypes = ['text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date', 'info', 'image', 'youtube'];
        foreach ($fields as $i => $field) {
            $label = trim($field['label'] ?? '');
            if ($label === '') {
                $errors[] = 'Pole #' . ($i + 1) . ' nemá vyplněný popisek.';
            }

            $type = $field['fieldType'] ?? '';
            if (!in_array($type, $validTypes, true)) {
                $errors[] = 'Pole #' . ($i + 1) . ' má neplatný typ.';
            }

            // Select/radio musi mit options
            if (in_array($type, ['select', 'radio'], true)) {
                $options = $field['options'] ?? [];
                if (empty($options)) {
                    $errors[] = "Pole '{$label}' (výběr/radio) musí mít alespoň jednu možnost.";
                }
            }
        }

        if (!empty($errors)) {
            $this->sendJson(['success' => false, 'errors' => $errors]);
        }

        $templateId = $data['id'] ?? null;

        try {
            if ($templateId) {
                // Kontrola vlastnictvi
                $existing = $this->formTemplateRepository->findById($templateId);
                if (!$existing || (!$this->getUser()->isInRole('admin') && $existing->created_by !== $this->getUser()->getId())) {
                    $this->sendJson(['success' => false, 'errors' => ['Nemáte oprávnění upravovat tuto šablonu.']]);
                }

                // Editace
                $this->formTemplateRepository->update($templateId, [
                    'name' => $name,
                    'description' => trim($data['description'] ?? '') ?: null,
                ]);
            } else {
                // Nova sablona
                $template = $this->formTemplateRepository->create([
                    'name' => $name,
                    'description' => trim($data['description'] ?? '') ?: null,
                    'created_by' => $this->getUser()->getId(),
                ]);
                $templateId = $template->id;
            }

            // Priprav pole pro replaceFields
            $fieldData = [];
            foreach ($fields as $field) {
                $fieldData[] = [
                    'dbId' => $field['dbId'] ?? null,
                    'field_type' => $field['fieldType'],
                    'label' => trim($field['label']),
                    'placeholder' => trim($field['placeholder'] ?? '') ?: null,
                    'is_required' => (bool) ($field['isRequired'] ?? false),
                    'options' => $field['options'] ?? [],
                    'conditions' => $field['conditions'] ?? null,
                    'system_key' => $field['systemKey'] ?? null,
                ];
            }

            $this->formTemplateRepository->replaceFields($templateId, $fieldData);

            $this->sendJson(['success' => true, 'templateId' => $templateId]);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'errors' => ['Chyba při ukládání: ' . $e->getMessage()]]);
        }
    }

    /**
     * Smazani sablony (soft delete)
     */
    public function handleDeleteTemplate(int $id): void
    {
        $template = $this->formTemplateRepository->findById($id);
        if (!$template) {
            $this->flashMessage('Šablona nebyla nalezena.', 'error');
            $this->redirect('this');
            return;
        }

        if (!$this->getUser()->isInRole('admin') && $template->created_by !== $this->getUser()->getId()) {
            $this->flashMessage('Nemáte oprávnění smazat tuto šablonu.', 'error');
            $this->redirect('this');
            return;
        }

        $eventCount = $this->formTemplateRepository->getEventCount($id);
        if ($eventCount > 0) {
            $this->flashMessage("Šablonu nelze smazat - je přiřazena k {$eventCount} akci/akcím.", 'error');
            $this->redirect('this');
            return;
        }

        $this->formTemplateRepository->delete($id);
        $this->flashMessage("Šablona '{$template->name}' byla smazána.", 'success');
        $this->redirect('this');
    }
}
