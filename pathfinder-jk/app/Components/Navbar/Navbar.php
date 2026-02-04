<?php

declare(strict_types=1);

namespace App\Components\Navbar;

use App\Model\Repository\UserRepository;
use Nette\Application\UI\Control;

final class Navbar extends Control
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/Navbar.latte');
        $this->template->user = $this->getPresenter()->getUser();
        $this->template->basePath = $this->getPresenter()->template->basePath;
        $this->template->presenter = $this->getPresenter();
        $this->template->isHomepage = $this->getPresenter()->isLinkCurrent('Home:default');

        // Get display name for logged-in user
        $displayName = null;
        if ($this->getPresenter()->getUser()->isLoggedIn()) {
            $userData = $this->userRepository->findById($this->getPresenter()->getUser()->getId());
            if ($userData) {
                $displayName = $userData->nickname ?: $userData->first_name;
            }
        }
        $this->template->displayName = $displayName;

        $this->template->render();
    }
}
