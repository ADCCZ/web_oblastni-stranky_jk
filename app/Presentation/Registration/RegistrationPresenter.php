<?php

declare(strict_types=1);

namespace App\Presentation\Registration;

use App\Model\Repository\EventRepository;
use App\Model\Repository\FormTemplateRepository;
use App\Model\Repository\RegistrationRepository;
use App\Model\Repository\UserRepository;
use App\Presentation\BasePresenter;
use App\Services\ExcelExportService;
use Nette\Application\Responses\FileResponse;
use Nette\Utils\Json;

final class RegistrationPresenter extends BasePresenter
{
    public function __construct(
        private EventRepository $eventRepository,
        private FormTemplateRepository $formTemplateRepository,
        private RegistrationRepository $registrationRepository,
        private UserRepository $userRepository,
        private ExcelExportService $excelExportService,
    ) {
    }

    /**
     * Registracni formular pro uzivatele
     */
    public function renderForm(int $eventId): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage('Pro registraci se musíte přihlásit.', 'warning');
            $this->redirect('Sign:in');
        }

        $event = $this->eventRepository->findById($eventId);
        if (!$event || $event->registration_type !== 'internal' || !$event->form_template_id) {
            $this->flashMessage('Tato akce nepodporuje online registraci.', 'error');
            $this->redirect('Event:default');
        }

        if (!$event->is_published) {
            $this->flashMessage('Akce není publikována.', 'error');
            $this->redirect('Event:default');
        }

        // Kontrola deadline
        if ($event->registration_to && new \DateTime() > (clone $event->registration_to)->setTime(23, 59, 59)) {
            $this->flashMessage('Registrace na tuto akci byla uzavřena.', 'warning');
            $this->redirect('Event:show', ['slug' => $event->slug]);
        }

        // Kontrola kapacity
        $regCount = $this->registrationRepository->getCountByEvent($eventId);
        $isFull = $event->capacity && $regCount >= $event->capacity;

        // Kontrola duplicitni registrace
        $existing = $this->registrationRepository->findByUserAndEvent($this->getUser()->getId(), $eventId);
        $alreadyRegistered = $existing && $existing->status !== 'cancelled';

        // Nacti sablonu a pole
        $templateData = $this->formTemplateRepository->getTemplateWithFields($event->form_template_id);
        if (!$templateData) {
            $this->flashMessage('Šablona formuláře nebyla nalezena.', 'error');
            $this->redirect('Event:show', ['slug' => $event->slug]);
        }

        // Predvyplneni z profilu
        $prefill = [];
        $userData = $this->userRepository->findById($this->getUser()->getId());
        if ($userData) {
            $prefill = [
                'first_name' => $userData->first_name,
                'last_name' => $userData->last_name,
                'is_pathfinder_member' => (bool) $userData->is_pathfinder_member,
                'street' => $userData->street ?? '',
                'city' => $userData->city ?? '',
                'zip' => $userData->zip ?? '',
                'birth_date' => $userData->birth_date ? (string) $userData->birth_date : '',
            ];
        }

        // Predvyplneni profilovych poli (systemKey -> hodnota z profilu)
        $profilePrefill = [];
        if ($userData) {
            $profileMap = [
                'email' => $userData->email ?? '',
                'phone' => $userData->phone ?? '',
                'is_vegetarian' => (bool) ($userData->is_vegetarian ?? false),
                'dietary_notes' => $userData->dietary_notes ?? '',
                'allergies' => $userData->allergies ?? '',
                'health_notes' => $userData->health_notes ?? '',
                'medications' => $userData->medications ?? '',
                'can_swim' => $userData->can_swim !== null ? ($userData->can_swim ? 'Ano' : 'Ne') : '',
                'clothing_size' => $userData->clothing_size ?? '',
            ];
            foreach ($templateData['fields'] as $f) {
                if ($f->system_key && isset($profileMap[$f->system_key])) {
                    $profilePrefill[$f->id] = $profileMap[$f->system_key];
                }
            }
        }

        // JSON pro JS (conditions, auto-prefill)
        $fieldsJson = Json::encode(array_map(function ($f) {
            return [
                'id' => $f->id,
                'type' => $f->field_type,
                'label' => $f->label,
                'required' => (bool) $f->is_required,
                'conditions' => $f->conditions ? Json::decode($f->conditions, forceArrays: true) : null,
                'systemKey' => $f->system_key,
            ];
        }, $templateData['fields']));

        $this->template->event = $event;
        $this->template->formTemplate = $templateData['template'];
        $this->template->fields = $templateData['fields'];
        $this->template->prefill = $prefill;
        $this->template->profilePrefill = $profilePrefill;
        $this->template->fieldsJson = $fieldsJson;
        $this->template->isFull = $isFull;
        $this->template->alreadyRegistered = $alreadyRegistered;
    }

    /**
     * AJAX: Odeslani registrace
     */
    public function handleSubmitRegistration(): void
    {
        $this->getHttpResponse()->setContentType('application/json');

        if (!$this->getUser()->isLoggedIn()) {
            $this->sendJson(['success' => false, 'errors' => ['Musíte být přihlášeni.']]);
        }

        $rawInput = file_get_contents('php://input');
        if (!$rawInput) {
            $this->sendJson(['success' => false, 'errors' => ['Prázdný požadavek.']]);
        }

        try {
            $data = Json::decode($rawInput, forceArrays: true);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'errors' => ['Neplatný formát dat.']]);
        }

        $eventId = (int) ($data['eventId'] ?? 0);
        $event = $this->eventRepository->findById($eventId);

        if (!$event || $event->registration_type !== 'internal' || !$event->form_template_id) {
            $this->sendJson(['success' => false, 'errors' => ['Neplatná akce.']]);
        }

        // Kontrola deadline
        if ($event->registration_to && new \DateTime() > (clone $event->registration_to)->setTime(23, 59, 59)) {
            $this->sendJson(['success' => false, 'errors' => ['Registrace byla uzavřena.']]);
        }

        // Kontrola kapacity
        $regCount = $this->registrationRepository->getCountByEvent($eventId);
        if ($event->capacity && $regCount >= $event->capacity) {
            $this->sendJson(['success' => false, 'errors' => ['Kapacita akce je naplněna.']]);
        }

        // Kontrola duplicity
        $userId = $this->getUser()->getId();
        if ($this->registrationRepository->isUserRegistered($userId, $eventId)) {
            $this->sendJson(['success' => false, 'errors' => ['Na tuto akci jste již registrováni.']]);
        }

        // Validace zakladnich poli
        $baseFields = $data['baseFields'] ?? [];
        $errors = [];

        $firstName = trim($baseFields['first_name'] ?? '');
        $lastName = trim($baseFields['last_name'] ?? '');
        $street = trim($baseFields['street'] ?? '');
        $city = trim($baseFields['city'] ?? '');
        $zip = trim($baseFields['zip'] ?? '');
        $birthDate = $baseFields['birth_date'] ?? '';

        if ($firstName === '') {
            $errors[] = 'Jméno je povinné.';
        }
        if ($lastName === '') {
            $errors[] = 'Příjmení je povinné.';
        }
        if ($street === '') {
            $errors[] = 'Ulice a č.p. je povinné.';
        }
        if ($city === '') {
            $errors[] = 'Město je povinné.';
        }
        if ($zip === '') {
            $errors[] = 'PSČ je povinné.';
        }
        if ($birthDate === '') {
            $errors[] = 'Datum narození je povinné.';
        }

        // Nacti pole sablony
        $templateData = $this->formTemplateRepository->getTemplateWithFields($event->form_template_id);
        $fields = $templateData['fields'];

        // Validace sablonovych poli (preskoc zobrazovaci typy)
        $displayOnlyTypes = ['info', 'image', 'youtube'];
        $fieldValues = $data['fields'] ?? [];

        foreach ($fields as $field) {
            if (in_array($field->field_type, $displayOnlyTypes, true)) {
                continue;
            }
            $key = (string) $field->id;
            $value = $fieldValues[$key] ?? null;

            if ($field->is_required && ($value === null || $value === '')) {
                $errors[] = "Pole '{$field->label}' je povinné.";
            }
        }

        if (!empty($errors)) {
            $this->sendJson(['success' => false, 'errors' => $errors]);
        }

        // Ulozeni
        try {
            $registration = $this->registrationRepository->create([
                'user_id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'event_id' => $eventId,
                'status' => 'pending',
                'note' => trim($data['note'] ?? '') ?: null,
                'is_pathfinder_member' => !empty($baseFields['is_pathfinder_member']),
                'street' => trim($baseFields['street'] ?? '') ?: null,
                'city' => trim($baseFields['city'] ?? '') ?: null,
                'zip' => trim($baseFields['zip'] ?? '') ?: null,
                'birth_date' => !empty($baseFields['birth_date']) ? $baseFields['birth_date'] : null,
            ]);

            $responses = [];
            foreach ($fields as $field) {
                if (in_array($field->field_type, $displayOnlyTypes, true)) {
                    continue;
                }
                $key = (string) $field->id;
                $value = $fieldValues[$key] ?? null;
                if ($value !== null && $value !== '') {
                    $responses[$field->id] = (string) $value;
                }
            }

            $this->registrationRepository->createResponses($registration->id, $responses);

            $this->sendJson(['success' => true, 'message' => 'Registrace byla úspěšně odeslána.']);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'errors' => ['Chyba při ukládání registrace.']]);
        }
    }

    /**
     * Seznam registraci pro vedouci/adminy
     */
    public function renderList(int $eventId): void
    {
        if (!$this->getUser()->isLoggedIn()
            || (!$this->getUser()->isInRole('admin') && !$this->getUser()->isInRole('leader'))) {
            $this->flashMessage('Nemáte oprávnění.', 'error');
            $this->redirect('Home:');
        }

        $event = $this->eventRepository->findById($eventId);
        if (!$event) {
            $this->flashMessage('Akce nebyla nalezena.', 'error');
            $this->redirect('Event:default');
        }

        $registrations = $this->registrationRepository->findResponsesByEvent($eventId);

        // Nacti pole sablony (pro hlavicky, bez zobrazovacich typu)
        $displayOnlyTypes = ['info', 'image', 'youtube'];
        $fields = [];
        if ($event->form_template_id) {
            $templateData = $this->formTemplateRepository->getTemplateWithFields($event->form_template_id);
            if ($templateData) {
                foreach ($templateData['fields'] as $field) {
                    if (!in_array($field->field_type, $displayOnlyTypes, true)) {
                        $fields[] = $field;
                    }
                }
            }
        }

        $this->template->event = $event;
        $this->template->registrations = $registrations;
        $this->template->fields = $fields;
    }

    /**
     * Zmena stavu registrace (leader/admin)
     */
    public function handleChangeStatus(int $registrationId, string $status): void
    {
        if (!$this->getUser()->isInRole('admin') && !$this->getUser()->isInRole('leader')) {
            $this->flashMessage('Nemáte oprávnění.', 'error');
            $this->redirect('this');
            return;
        }

        $validStatuses = ['pending', 'confirmed', 'cancelled', 'waitlist'];
        if (!in_array($status, $validStatuses, true)) {
            $this->flashMessage('Neplatný stav.', 'error');
            $this->redirect('this');
            return;
        }

        $reg = $this->registrationRepository->findById($registrationId);
        if (!$reg) {
            $this->flashMessage('Registrace nebyla nalezena.', 'error');
            $this->redirect('this');
            return;
        }

        $this->registrationRepository->update($registrationId, ['status' => $status]);

        $statusNames = ['pending' => 'Čekající', 'confirmed' => 'Potvrzená', 'cancelled' => 'Zrušená', 'waitlist' => 'Náhradník'];
        $this->flashMessage("Stav registrace změněn na: {$statusNames[$status]}.", 'success');
        $this->redirect('this');
    }

    /**
     * Zruseni vlastni registrace
     */
    public function handleCancelRegistration(int $registrationId): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $reg = $this->registrationRepository->findById($registrationId);
        if (!$reg || $reg->user_id !== $this->getUser()->getId()) {
            $this->flashMessage('Registrace nebyla nalezena.', 'error');
            $this->redirect('this');
            return;
        }

        $this->registrationRepository->update($registrationId, ['status' => 'cancelled']);
        $this->flashMessage('Vaše registrace byla zrušena.', 'success');

        $event = $this->eventRepository->findById($reg->event_id);
        if ($event) {
            $this->redirect('Event:show', ['slug' => $event->slug]);
        }
        $this->redirect('Event:default');
    }

    /**
     * Excel export registraci (leader/admin)
     */
    public function handleExportExcel(int $eventId): void
    {
        if (!$this->getUser()->isInRole('admin') && !$this->getUser()->isInRole('leader')) {
            $this->flashMessage('Nemáte oprávnění.', 'error');
            $this->redirect('this');
            return;
        }

        $event = $this->eventRepository->findById($eventId);
        if (!$event) {
            $this->flashMessage('Akce nebyla nalezena.', 'error');
            $this->redirect('Event:default');
            return;
        }

        $filepath = $this->excelExportService->generateEventRegistrations(
            $eventId,
            $event->form_template_id,
            $event->title,
        );

        if (!$filepath) {
            $this->flashMessage('Žádné registrace k exportu.', 'warning');
            $this->redirect('this');
            return;
        }

        $this->sendResponse(new FileResponse($filepath, basename($filepath), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', true));
    }
}
