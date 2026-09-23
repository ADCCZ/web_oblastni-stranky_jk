<?php

declare(strict_types=1);

namespace App\Presentation\Password;

use App\Model\Repository\PasswordResetRepository;
use App\Model\Repository\UserRepository;
use App\Presentation\BasePresenter;
use App\Services\MailService;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;

final class PasswordPresenter extends BasePresenter
{
    private ?int $resetUserId = null;
    private ?string $resetCode = null;

    public function __construct(
        private UserRepository $userRepository,
        private PasswordResetRepository $passwordResetRepository,
        private MailService $mailService,
        private Passwords $passwords,
    ) {
    }

    /**
     * Krok 1: Zadání emailu
     */
    public function renderReset(): void
    {
        // Pokud je přihlášen, předvyplníme email
        if ($this->getUser()->isLoggedIn()) {
            $this->template->userEmail = $this->getUser()->getIdentity()->email ?? null;
        }
    }

    /**
     * Krok 2: Ověření kódu
     */
    public function actionVerify(): void
    {
        $session = $this->getSession()->getSection('password_reset');
        $this->resetUserId = $session->get('user_id');

        if (!$this->resetUserId) {
            $this->flashMessage('Nejprve zadejte svůj email.', 'warning');
            $this->redirect('Password:reset');
        }
    }

    public function renderVerify(): void
    {
        $session = $this->getSession()->getSection('password_reset');
        $email = $session->get('email');
        $this->template->email = $email;
    }

    /**
     * Krok 3: Nové heslo
     */
    public function actionNewPassword(): void
    {
        $session = $this->getSession()->getSection('password_reset');
        $this->resetUserId = $session->get('user_id');
        $verified = $session->get('verified');

        if (!$this->resetUserId || !$verified) {
            $this->flashMessage('Nejprve ověřte kód z emailu.', 'warning');
            $this->redirect('Password:reset');
        }
    }

    /**
     * Formulář pro zadání emailu
     */
    protected function createComponentEmailForm(): Form
    {
        $form = new Form;

        $form->addEmail('email', 'E-mail')
            ->setRequired('Zadejte svůj e-mail');

        $form->addSubmit('send', 'Odeslat kód');

        $form->onSuccess[] = [$this, 'emailFormSucceeded'];

        return $form;
    }

    public function emailFormSucceeded(Form $form, \stdClass $data): void
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (!$user) {
            // Z bezpečnostních důvodů neříkáme, že email neexistuje
            $this->flashMessage('Pokud email existuje v naší databázi, odeslali jsme na něj ověřovací kód.', 'info');
            $this->redirect('this');
            return;
        }

        // Vytvoř kód a odešli email
        $code = $this->passwordResetRepository->createResetCode($user->id);

        try {
            $this->mailService->sendPasswordResetCode(
                $user->email,
                $code,
                $user->first_name
            );
        } catch (\Exception $e) {
            $this->flashMessage('Nepodařilo se odeslat email. Zkuste to prosím později.', 'error');
            $this->redirect('this');
            return;
        }

        // Ulož do session
        $session = $this->getSession()->getSection('password_reset');
        $session->set('user_id', $user->id);
        $session->set('email', $user->email);
        $session->setExpiration('20 minutes');

        $this->flashMessage('Ověřovací kód byl odeslán na váš email.', 'success');
        $this->redirect('Password:verify');
    }

    /**
     * Formulář pro ověření kódu
     */
    protected function createComponentVerifyForm(): Form
    {
        $form = new Form;

        $form->addText('code', 'Ověřovací kód')
            ->setRequired('Zadejte 6-místný kód z emailu')
            ->addRule($form::Pattern, 'Kód musí mít 6 číslic', '[0-9]{6}');

        $form->addSubmit('verify', 'Ověřit');

        $form->onSuccess[] = [$this, 'verifyFormSucceeded'];

        return $form;
    }

    public function verifyFormSucceeded(Form $form, \stdClass $data): void
    {
        $session = $this->getSession()->getSection('password_reset');
        $userId = $session->get('user_id');

        if (!$userId) {
            $this->flashMessage('Session vypršela. Začněte prosím znovu.', 'error');
            $this->redirect('Password:reset');
            return;
        }

        if (!$this->passwordResetRepository->verifyCode($userId, $data->code)) {
            $form->addError('Neplatný nebo expirovaný kód.');
            return;
        }

        // Označ kód jako ověřený (ale ještě ne použitý)
        $session->set('verified', true);
        $session->set('code', $data->code);

        $this->redirect('Password:newPassword');
    }

    /**
     * Formulář pro nové heslo
     */
    protected function createComponentNewPasswordForm(): Form
    {
        $form = new Form;

        $form->addPassword('password', 'Nové heslo')
            ->setRequired('Zadejte nové heslo')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků', 8);

        $form->addPassword('password_confirm', 'Heslo znovu')
            ->setRequired('Potvrďte nové heslo')
            ->addRule($form::Equal, 'Hesla se neshodují', $form['password']);

        $form->addSubmit('save', 'Nastavit nové heslo');

        $form->onSuccess[] = [$this, 'newPasswordFormSucceeded'];

        return $form;
    }

    public function newPasswordFormSucceeded(Form $form, \stdClass $data): void
    {
        $session = $this->getSession()->getSection('password_reset');
        $userId = $session->get('user_id');
        $code = $session->get('code');
        $verified = $session->get('verified');

        if (!$userId || !$code || !$verified) {
            $this->flashMessage('Session vypršela. Začněte prosím znovu.', 'error');
            $this->redirect('Password:reset');
            return;
        }

        // Znovu ověř kód (pro jistotu)
        if (!$this->passwordResetRepository->verifyCode($userId, $code)) {
            $this->flashMessage('Kód vypršel. Začněte prosím znovu.', 'error');
            $this->redirect('Password:reset');
            return;
        }

        // Změň heslo
        $this->userRepository->update($userId, [
            'password_hash' => $this->passwords->hash($data->password),
        ]);

        // Označ kód jako použitý
        $this->passwordResetRepository->markCodeAsUsed($userId, $code);

        // Vymaž session
        $session->remove('user_id');
        $session->remove('email');
        $session->remove('code');
        $session->remove('verified');

        $this->flashMessage('Heslo bylo úspěšně změněno. Nyní se můžete přihlásit.', 'success');
        $this->redirect('Sign:in');
    }

    /**
     * Znovu odeslat kód
     */
    public function handleResendCode(): void
    {
        $session = $this->getSession()->getSection('password_reset');
        $userId = $session->get('user_id');
        $email = $session->get('email');

        if (!$userId || !$email) {
            $this->flashMessage('Session vypršela. Začněte prosím znovu.', 'error');
            $this->redirect('Password:reset');
            return;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            $this->redirect('Password:reset');
            return;
        }

        $code = $this->passwordResetRepository->createResetCode($userId);

        try {
            $this->mailService->sendPasswordResetCode($email, $code, $user->first_name);
            $this->flashMessage('Nový kód byl odeslán.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Nepodařilo se odeslat email.', 'error');
        }

        $this->redirect('this');
    }
}
