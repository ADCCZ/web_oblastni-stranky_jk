<?php

declare(strict_types=1);

namespace App\Components\Navbar;

use Nette\Application\UI\Control;

final class Navbar extends Control
{
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/Navbar.latte');
        $this->template->user = $this->getPresenter()->getUser();
        $this->template->basePath = $this->getPresenter()->template->basePath;
        $this->template->render();
    }
}
