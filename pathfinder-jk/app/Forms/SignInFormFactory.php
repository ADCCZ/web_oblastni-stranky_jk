<?php

declare(strict_types=1);

namespace App\Forms;

use App\Model\Repository\UserRepository;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nette\Security\User;

final class SignInFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private User $user,
        private UserRepository $userRepository,
        private Passwords $passwords,
    ) {
    }

    /**
     * @param callable $onSuccess Zavolá se při úspěšném přihlášení (bez 2FA nebo po 2FA)
     * @param callable|null $onTwoFactorRequired Zavolá se pokud je vyžadováno 2FA
     */
    public function create(callable $onSuccess, ?callable $onTwoFactorRequired = null): Form
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

        $form->onSuccess[] = function (Form $form, \stdClass $data) use ($onSuccess, $onTwoFactorRequired): void {
            // Najdi uživatele
            $user = $this->userRepository->findByEmail($data->email);

            if (!$user) {
                $form->addError('Nesprávný e-mail nebo heslo.');
                return;
            }

            // Zkontroluj, zda je účet aktivní
            if (!$user->is_active) {
                $form->addError('Váš účet byl deaktivován.');
                return;
            }

            // Ověř heslo (pokud má uživatel heslo)
            if ($user->password_hash) {
                if (!$this->passwords->verify($data->password, $user->password_hash)) {
                    $form->addError('Nesprávný e-mail nebo heslo.');
                    return;
                }
            } else {
                // Uživatel nemá heslo (OAuth only)
                $form->addError('Tento účet nemá nastavené heslo. Přihlaste se pomocí sociální sítě.');
                return;
            }

            // Zkontroluj 2FA
            $twoFactorType = $user->two_factor_type ?? 'none';
            $twoFactorVerified = (bool) ($user->two_factor_verified ?? false);

            if ($twoFactorType !== 'none' && $twoFactorVerified && $onTwoFactorRequired) {
                // Vyžaduje 2FA - zavolej callback s user ID a nastavením
                $onTwoFactorRequired($user->id, $twoFactorType, $data->remember);
                return;
            }

            // Bez 2FA - přihlaš přímo
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
