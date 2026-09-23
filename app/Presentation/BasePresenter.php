<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarFactory;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    private NavbarFactory $navbarFactory;

    public function injectNavbarFactory(NavbarFactory $navbarFactory): void
    {
        $this->navbarFactory = $navbarFactory;
    }

    public function beforeRender(): void
    {
        parent::beforeRender();
        $id = $this->getParameterId('flash');
        $this->template->serverFlashes = (array) $this->getPresenter()->getFlashSession()->get($id);
    }

    protected function createComponentNavbar(): Navbar
    {
        return $this->navbarFactory->create();
    }
}
