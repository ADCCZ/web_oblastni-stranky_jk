<?php

declare(strict_types=1);

namespace App\Forms;

use App\Model\Repository\UserRepository;
use Nette\Application\UI\Form;
use Nette\Security\User;

final class SignUpFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private UserRepository $userRepository,
        private User $user,
    ) {
    }

    public function create(callable $onSuccess): Form
    {
        $form = $this->formFactory->create();

        $form->addText('first_name', 'Jméno')
            ->setRequired('Zadejte jméno.')
            ->setMaxLength(100)
            ->setHtmlAttribute('placeholder', 'Jan')
            ->setHtmlAttribute('class', 'form-input');

        $form->addText('last_name', 'Příjmení')
            ->setRequired('Zadejte příjmení.')
            ->setMaxLength(100)
            ->setHtmlAttribute('placeholder', 'Novák')
            ->setHtmlAttribute('class', 'form-input');

        $form->addEmail('email', 'E-mail')
            ->setRequired('Zadejte e-mail.')
            ->setHtmlAttribute('placeholder', 'vas@email.cz')
            ->setHtmlAttribute('class', 'form-input');

        $form->addText('phone', 'Telefon')
            ->setMaxLength(20)
            ->setHtmlAttribute('placeholder', '+420 123 456 789')
            ->setHtmlAttribute('class', 'form-input');

        $form->addPassword('password', 'Heslo')
            ->setRequired('Zadejte heslo.')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8)
            ->setHtmlAttribute('placeholder', 'Min. 8 znaků')
            ->setHtmlAttribute('class', 'form-input');

        $form->addPassword('password_confirm', 'Heslo znovu')
            ->setRequired('Zadejte heslo znovu.')
            ->addRule($form::Equal, 'Hesla se neshodují.', $form['password'])
            ->setHtmlAttribute('placeholder', 'Zopakujte heslo')
            ->setHtmlAttribute('class', 'form-input');

        $form->addCheckbox('newsletter', 'Chci dostávat novinky e-mailem')
            ->setHtmlAttribute('class', 'form-checkbox');

        $form->addCheckbox('terms', 'Souhlasím s podmínkami užití a zpracováním osobních údajů')
            ->setRequired('Pro registraci musíte souhlasit s podmínkami.')
            ->setHtmlAttribute('class', 'form-checkbox');

        $form->addSubmit('send', 'Zaregistrovat se')
            ->setHtmlAttribute('class', 'btn-primary');

        $form->onSuccess[] = function (Form $form, \stdClass $data) use ($onSuccess): void {
            // Check if email exists
            if ($this->userRepository->emailExists($data->email)) {
                $form->addError('Uživatel s tímto e-mailem již existuje.');
                return;
            }

            // Create user
            $user = $this->userRepository->create([
                'email' => $data->email,
                'password_hash' => password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'phone' => $data->phone ?: null,
                'role' => 'member',
                'is_active' => true,
                'newsletter' => $data->newsletter ? 1 : 0,
            ]);

            // Auto-login after registration
            $this->user->login($data->email, $data->password);

            $onSuccess();
        };

        return $form;
    }
}
