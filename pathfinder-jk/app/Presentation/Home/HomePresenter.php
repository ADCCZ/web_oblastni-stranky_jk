<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use App\Model\Repository\EventRepository;
use Nette\Application\UI\Presenter;

final class HomePresenter extends Presenter
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {
    }

    public function renderDefault(): void
    {
        $this->template->events = $this->eventRepository->findUpcoming(6);
    }
}
