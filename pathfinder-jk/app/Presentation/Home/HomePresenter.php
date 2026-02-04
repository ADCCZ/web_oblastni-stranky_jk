<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use App\Model\Repository\EventRepository;
use App\Model\Repository\NewsRepository;
use App\Presentation\BasePresenter;

final class HomePresenter extends BasePresenter
{
    public function __construct(
        private EventRepository $eventRepository,
        private NewsRepository $newsRepository,
    ) {
    }

    public function renderDefault(): void
    {
        // Načti nadcházející akce
        try {
            $this->template->events = $this->eventRepository->findUpcoming(6);
        } catch (\Throwable $e) {
            $this->template->events = [];
        }

        // Načti aktuality
        try {
            $this->template->news = $this->newsRepository->findLatest(6);
        } catch (\Throwable $e) {
            $this->template->news = [];
        }
    }
}
