<?php

declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Security\User;

final class SignInFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private User $user,
    ) {
    }

    public function create(callable $onSuccess): Form
    {
        $form = $this->formFactory->create();

        $form->addEmail('email', 'E-mail')
            ->setRequired('Zadejte e-mail.')
            ->setHtmlAttribute('placeholder', 'vas@email.cz')
            ->setHtmlAttribute('class', 'form-input');

        $form->addPassword('password', 'Heslo')
            ->setRequired('Zadejte heslo.')
            ->setHtmlAttribute('placeholder', 'Vaše heslo')
            ->setHtmlAttribute('class', 'form-input');

        $form->addCheckbox('remember', 'Zapamatovat si mě');

        $form->addSubmit('send', 'Přihlásit se')
            ->setHtmlAttribute('class', 'btn-primary');

        $form->onSuccess[] = function (Form $form, \stdClass $data) use ($onSuccess): void {
            try {
                $this->user->setExpiration($data->remember ? '14 days' : '20 minutes');
                $this->user->login($data->email, $data->password);
                $onSuccess();
            } catch (\Nette\Security\AuthenticationException $e) {
                $form->addError($e->getMessage());
            }
        };

        return $form;
    }
}
