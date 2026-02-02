<?php

declare(strict_types=1);

namespace App\Presentation\Sign;

use App\Forms\SignInFormFactory;
use App\Forms\SignUpFormFactory;
use App\Presentation\BasePresenter;
use Nette\Application\UI\Form;

final class SignPresenter extends BasePresenter
{
    public function __construct(
        private SignInFormFactory $signInFormFactory,
        private SignUpFormFactory $signUpFormFactory,
    ) {
    }

    /**
     * Sign-in form factory
     */
    protected function createComponentSignInForm(): Form
    {
        return $this->signInFormFactory->create(function (): void {
            $this->flashMessage('Byli jste úspěšně přihlášeni.', 'success');
            $this->redirect('Home:');
        });
    }

    /**
     * Sign-up form factory
     */
    protected function createComponentSignUpForm(): Form
    {
        return $this->signUpFormFactory->create(function (): void {
            $this->flashMessage('Registrace byla úspěšná. Vítejte!', 'success');
            $this->redirect('Home:');
        });
    }

    /**
     * Sign in action
     */
    public function actionIn(): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Home:');
        }
    }

    /**
     * Sign up action
     */
    public function actionUp(): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Home:');
        }
    }

    /**
     * Sign out action
     */
    public function actionOut(): void
    {
        $this->getUser()->logout(true);
        $this->flashMessage('Byli jste odhlášeni.', 'info');
        $this->redirect('Home:');
    }
}
