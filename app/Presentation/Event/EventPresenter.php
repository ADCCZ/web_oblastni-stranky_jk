<?php

declare(strict_types=1);

namespace App\Presentation\Event;

use App\Model\Repository\EventRepository;
use App\Model\Repository\FormTemplateRepository;
use App\Presentation\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;

final class EventPresenter extends BasePresenter
{
    private string $uploadDir;
    private ?ActiveRow $editingEvent = null;

    public function __construct(
        private EventRepository $eventRepository,
        private FormTemplateRepository $formTemplateRepository,
    ) {
    }

    public function startup(): void
    {
        parent::startup();
        $this->uploadDir = $this->getHttpRequest()->getUrl()->getBasePath();
    }

    public function renderDefault(?int $category = null): void
    {
        $categories = $this->eventRepository->findAllCategories();
        $this->template->categories = $categories;
        $this->template->activeCategory = $category;

        // Check if user can add events (admin or leader)
        $canManage = $this->getUser()->isLoggedIn()
            && ($this->getUser()->isInRole('admin') || $this->getUser()->isInRole('leader'));
        $this->template->canManage = $canManage;

        try {
            if ($category) {
                $this->template->upcomingEvents = $this->eventRepository->findUpcomingByCategory($category);
                $this->template->pastEvents = $this->eventRepository->findPastByCategory($category);
            } else {
                $this->template->upcomingEvents = $this->eventRepository->findUpcoming(50);
                $this->template->pastEvents = $this->eventRepository->findPast(50);
            }
        } catch (\Throwable $e) {
            $this->template->upcomingEvents = [];
            $this->template->pastEvents = [];
        }
    }

    public function renderShow(string $slug): void
    {
        // Fetched regardless of publish state so an owning leader/admin can preview a draft;
        // visibility for everyone else is enforced right below.
        $event = $this->eventRepository->findBySlugIncludingUnpublished($slug);
        $canEdit = $event ? $this->canEditEvent($event) : false;

        if (!$event || (!$event->is_published && !$canEdit)) {
            $this->flashMessage('Akce nebyla nalezena.', 'error');
            $this->redirect('Event:default');
        }

        $this->template->event = $event;
        $this->template->registrationCount = $this->eventRepository->getRegistrationCount($event->id);
        $this->template->canManage = $this->getUser()->isLoggedIn()
            && ($this->getUser()->isInRole('admin') || $this->getUser()->isInRole('leader'));
        $this->template->canEdit = $canEdit;
    }

    public function actionAdd(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage('Pro přidání akce se musíte přihlásit.', 'error');
            $this->redirect('Sign:in');
        }

        if (!$this->getUser()->isInRole('admin') && !$this->getUser()->isInRole('leader')) {
            $this->flashMessage('Nemáte oprávnění přidávat akce.', 'error');
            $this->redirect('Event:default');
        }

        $this->template->categories = $this->eventRepository->findAllCategories();
    }

    public function actionEdit(int $id): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage('Pro úpravu akce se musíte přihlásit.', 'error');
            $this->redirect('Sign:in');
        }

        $event = $this->eventRepository->findById($id);
        if (!$event) {
            $this->flashMessage('Akce nebyla nalezena.', 'error');
            $this->redirect('Event:default');
        }

        if (!$this->canEditEvent($event)) {
            $this->flashMessage('Nemáte oprávnění upravovat tuto akci.', 'error');
            $this->redirect('Event:show', ['slug' => $event->slug]);
        }

        $this->editingEvent = $event;
        $this->template->event = $event;
        $this->template->categories = $this->eventRepository->findAllCategories();
    }

    private function canEditEvent(ActiveRow $event): bool
    {
        if (!$this->getUser()->isLoggedIn()) {
            return false;
        }

        if ($this->getUser()->isInRole('admin')) {
            return true;
        }

        return $this->getUser()->isInRole('leader') && (int) $event->created_by === (int) $this->getUser()->getId();
    }

    protected function createComponentEventForm(): Form
    {
        $form = new Form;

        $form->addText('title', 'Název akce')
            ->setRequired('Zadejte název akce')
            ->setMaxLength(255);

        $form->addTextArea('description', 'Krátký popis')
            ->setMaxLength(500)
            ->setHtmlAttribute('rows', 3);

        $form->addTextArea('content', 'Podrobný popis')
            ->setHtmlAttribute('rows', 8);

        $form->addUpload('image', 'Obrázek / PDF')
            ->addCondition($form::Filled)
            ->addRule($form::MimeType, 'Povolené formáty: JPG, PNG, GIF, WEBP, PDF.', [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
            ])
            ->addRule($form::MaxFileSize, 'Maximální velikost souboru je 10 MB.', 10 * 1024 * 1024);

        $form->addText('location', 'Místo konání')
            ->setMaxLength(255);

        $form->addText('location_url', 'Odkaz na místo (mapy)')
            ->setHtmlType('url')
            ->setHtmlAttribute('placeholder', 'https://mapy.cz/...')
            ->setMaxLength(500);

        $form->addText('start_date', 'Datum začátku')
            ->setRequired('Zadejte datum začátku')
            ->setHtmlType('datetime-local');

        $form->addText('end_date', 'Datum konce')
            ->setHtmlType('datetime-local');

        $form->addText('registration_to', 'Registrace do')
            ->setHtmlType('datetime-local');

        $form->addSelect('registration_type', 'Typ registrace', [
                'none' => 'Bez registrace',
                'external' => 'Externí formulář (odkaz)',
                'internal' => 'Interní formulář (šablona)',
            ])
            ->setDefaultValue('none');

        $form->addText('form_url', 'Odkaz na formulář')
            ->setHtmlType('url')
            ->setHtmlAttribute('placeholder', 'https://...')
            ->setMaxLength(500);

        if ($this->getUser()->isInRole('admin')) {
            $templates = $this->formTemplateRepository->findActive()->fetchPairs('id', 'name');
        } else {
            $templates = $this->formTemplateRepository->findActiveForUser($this->getUser()->getId())->fetchPairs('id', 'name');
        }
        $form->addSelect('form_template_id', 'Šablona formuláře', $templates)
            ->setPrompt('-- Vyberte šablonu --');

        $form->addText('form_button_text', 'Text tlačítka')
            ->setHtmlAttribute('placeholder', 'Např. Přihlásit se')
            ->setMaxLength(100);

        $form->addInteger('capacity', 'Kapacita')
            ->addCondition($form::Filled)
            ->addRule($form::Min, 'Kapacita musí být alespoň 1', 1);

        $form->addText('price', 'Cena (Kč)')
            ->setHtmlType('number')
            ->setHtmlAttribute('step', '0.01')
            ->setHtmlAttribute('min', '0');

        // Categories from DB
        $categories = $this->eventRepository->findAllCategories()->fetchPairs('id', 'name');
        $form->addSelect('category_id', 'Kategorie', $categories)
            ->setPrompt('-- Vyberte kategorii --');

        $form->addSelect('availability', 'Dostupnost', [
                'all' => 'Pro všechny',
                'leaders' => 'Pouze pro vůdce',
            ])
            ->setDefaultValue('all');

        $form->addCheckbox('is_published', 'Publikovat ihned')
            ->setDefaultValue(true);

        $form->addSubmit('save', $this->editingEvent !== null ? 'Uložit změny' : 'Vytvořit akci');

        if ($this->editingEvent !== null) {
            $event = $this->editingEvent;
            $form->setDefaults([
                'title' => $event->title,
                'description' => $event->description,
                'content' => $event->content,
                'location' => $event->location,
                'location_url' => $event->location_url,
                'start_date' => $event->start_date?->format('Y-m-d\TH:i'),
                'end_date' => $event->end_date?->format('Y-m-d\TH:i'),
                'registration_to' => $event->registration_to?->format('Y-m-d\TH:i'),
                'registration_type' => $event->registration_type,
                'form_url' => $event->form_url,
                'form_template_id' => $event->form_template_id,
                'form_button_text' => $event->form_button_text,
                'capacity' => $event->capacity,
                'price' => $event->price,
                'category_id' => $event->category_id,
                'availability' => $event->availability,
                'is_published' => (bool) $event->is_published,
            ]);
        }

        $form->onSuccess[] = [$this, 'eventFormSucceeded'];

        return $form;
    }

    public function eventFormSucceeded(Form $form, \stdClass $data): void
    {
        $isEdit = $this->editingEvent !== null;

        if ($isEdit) {
            // Slug stays the same on edit so existing links/bookmarks keep working.
            $slug = $this->editingEvent->slug;
        } else {
            // Generate slug from title, ensure uniqueness (including drafts, not just published events)
            $slug = Strings::webalize($data->title);
            $existing = $this->eventRepository->findBySlugIncludingUnpublished($slug);
            $counter = 1;
            $baseSlug = $slug;
            while ($existing) {
                $slug = $baseSlug . '-' . $counter;
                $existing = $this->eventRepository->findBySlugIncludingUnpublished($slug);
                $counter++;
            }
        }

        // Handle file upload - keep the existing image on edit unless a new one is uploaded
        $imagePath = $isEdit ? $this->editingEvent->image_path : null;
        /** @var FileUpload $image */
        $image = $data->image;
        if ($image && $image->isOk()) {
            $uploadPath = __DIR__ . '/../../../www/uploads/events';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $ext = strtolower(pathinfo($image->getSanitizedName(), PATHINFO_EXTENSION));
            $uniqueName = $slug . '-' . uniqid() . '.' . $ext;
            $image->move($uploadPath . '/' . $uniqueName);
            $imagePath = 'uploads/events/' . $uniqueName;
        }

        $eventData = [
            'title' => $data->title,
            'slug' => $slug,
            'description' => $data->description ?: null,
            'content' => $data->content ?: null,
            'image_path' => $imagePath,
            'location' => $data->location ?: null,
            'location_url' => $data->location_url ?: null,
            'start_date' => $data->start_date ? new \DateTime($data->start_date) : null,
            'end_date' => $data->end_date ? new \DateTime($data->end_date) : null,
            'registration_to' => $data->registration_to ? new \DateTime($data->registration_to) : null,
            'capacity' => $data->capacity ?: null,
            'price' => $data->price !== '' && $data->price !== null ? (float) $data->price : null,
            'category_id' => $data->category_id ?: null,
            'is_published' => $data->is_published ? 1 : 0,
            'availability' => $data->availability,
            'registration_type' => $data->registration_type ?? 'none',
            'form_url' => $data->registration_type === 'external' ? ($data->form_url ?: null) : null,
            'form_template_id' => $data->registration_type === 'internal' ? ($data->form_template_id ?: null) : null,
            'form_button_text' => $data->form_button_text ?: null,
        ];

        if ($isEdit) {
            $this->eventRepository->update($this->editingEvent->id, $eventData);
            $this->flashMessage('Akce byla úspěšně upravena.', 'success');
        } else {
            $eventData['created_by'] = $this->getUser()->getId();
            $this->eventRepository->create($eventData);
            $this->flashMessage('Akce byla úspěšně vytvořena.', 'success');
        }

        $this->redirect('Event:show', ['slug' => $slug]);
    }
}
