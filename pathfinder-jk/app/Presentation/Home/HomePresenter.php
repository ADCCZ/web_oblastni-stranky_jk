<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use App\Model\Repository\EventRepository;
use App\Presentation\BasePresenter;

final class HomePresenter extends BasePresenter
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {
    }

    public function renderDefault(): void
    {
        try {
            $this->template->events = $this->eventRepository->findUpcoming(6);
        } catch (\Throwable $e) {
            // Database table doesn't exist yet or other DB error
            $this->template->events = [];
        }
    }
}
