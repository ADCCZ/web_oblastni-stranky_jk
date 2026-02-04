<?php

declare(strict_types=1);

namespace App\Presentation\Profile;

use App\Model\Repository\UserRepository;
use App\Model\Repository\TwoFactorRepository;
use App\Presentation\BasePresenter;
use App\Security\OAuthService;
use App\Security\OAuthException;
use App\Services\MailService;
use App\Services\TotpService;
use Nette\Application\UI\Form;

final class ProfilePresenter extends BasePresenter
{
    public function __construct(
        private UserRepository $userRepository,
        private OAuthService $oauthService,
        private TwoFactorRepository $twoFactorRepository,
        private MailService $mailService,
        private TotpService $totpService,
    ) {
    }

    public function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage('Pro zobrazení profilu se musíte přihlásit.', 'warning');
            $this->redirect('Sign:in');
        }
    }

    public function renderDefault(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $this->template->profile = $userData;
        $this->template->identity = $this->getUser()->getIdentity();
    }

    /**
     * Stránka nastavení 2FA
     */
    public function renderSecurity(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $this->template->profile = $userData;
        $this->template->twoFactorType = $userData->two_factor_type ?? 'none';
        $this->template->twoFactorVerified = (bool) ($userData->two_factor_verified ?? false);
        $this->template->hasPassword = !empty($userData->password_hash);
    }

    /**
     * Nastavení 2FA přes authenticator
     */
    public function actionSetupTotp(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        // Vygeneruj nový secret (nebo použij existující neverifikovaný)
        $secret = $userData->two_factor_secret;
        if (!$secret || $userData->two_factor_type !== 'totp') {
            $secret = $this->totpService->generateSecret();
            $this->userRepository->update($userId, [
                'two_factor_type' => 'totp',
                'two_factor_secret' => $secret,
                'two_factor_verified' => false,
            ]);
        }

        $session = $this->getSession()->getSection('2fa_setup');
        $session->set('secret', $secret);
    }

    public function renderSetupTotp(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $secret = $userData->two_factor_secret;
        $qrUrl = $this->totpService->getQrCodeUrl($secret, $userData->email);

        $this->template->secret = $secret;
        $this->template->qrUrl = $qrUrl;
        $this->template->profile = $userData;
    }

    /**
     * Nastavení 2FA přes email - zobrazí ověřovací stránku
     */
    public function actionSetupEmail(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        // Nastav typ na email, ale ještě neverifikovaný
        $this->userRepository->update($userId, [
            'two_factor_type' => 'email',
            'two_factor_secret' => null,
            'two_factor_verified' => false,
        ]);

        // Vygeneruj a odešli ověřovací kód
        $code = $this->twoFactorRepository->createCode($userId, 'email');

        try {
            $this->mailService->sendTwoFactorCode($userData->email, $code, $userData->first_name);
        } catch (\Exception $e) {
            $this->flashMessage('Nepodařilo se odeslat ověřovací kód.', 'error');
            $this->redirect('Profile:security');
        }
    }

    public function renderSetupEmail(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $this->template->profile = $userData;
    }

    /**
     * Formulář pro ověření email 2FA kódu
     */
    protected function createComponentEmailVerifyForm(): Form
    {
        $form = new Form;

        $form->addText('code', 'Ověřovací kód')
            ->setRequired('Zadejte 6-místný kód')
            ->addRule($form::Pattern, 'Kód musí mít 6 číslic', '[0-9]{6}');

        $form->addSubmit('verify', 'Ověřit a aktivovat');

        $form->onSuccess[] = [$this, 'emailVerifyFormSucceeded'];

        return $form;
    }

    public function emailVerifyFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();

        if (!$this->twoFactorRepository->verifyCode($userId, $data->code, 'email')) {
            $form->addError('Neplatný nebo expirovaný kód.');
            return;
        }

        $this->userRepository->update($userId, [
            'two_factor_verified' => true,
        ]);

        $this->flashMessage('Dvoufázové ověření přes email bylo úspěšně aktivováno.', 'success');
        $this->redirect('Profile:security');
    }

    /**
     * Znovu odeslat ověřovací kód pro email 2FA setup
     */
    public function handleResendSetupCode(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $code = $this->twoFactorRepository->createCode($userId, 'email');

        try {
            $this->mailService->sendTwoFactorCode($userData->email, $code, $userData->first_name);
            $this->flashMessage('Nový kód byl odeslán.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Nepodařilo se odeslat kód.', 'error');
        }

        $this->redirect('this');
    }

    /**
     * Vypnutí 2FA
     */
    public function actionDisable2fa(): void
    {
        $userId = $this->getUser()->getId();

        $this->userRepository->update($userId, [
            'two_factor_type' => 'none',
            'two_factor_secret' => null,
            'two_factor_verified' => false,
        ]);

        $this->flashMessage('Dvoufázové ověření bylo vypnuto.', 'success');
        $this->redirect('Profile:security');
    }

    /**
     * Formulář pro ověření TOTP kódu při nastavení
     */
    protected function createComponentTotpVerifyForm(): Form
    {
        $form = new Form;

        $form->addText('code', 'Kód z aplikace')
            ->setRequired('Zadejte 6-místný kód')
            ->addRule($form::Pattern, 'Kód musí mít 6 číslic', '[0-9]{6}');

        $form->addSubmit('verify', 'Ověřit a aktivovat');

        $form->onSuccess[] = [$this, 'totpVerifyFormSucceeded'];

        return $form;
    }

    public function totpVerifyFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        if (!$this->totpService->verify($userData->two_factor_secret, $data->code)) {
            $form->addError('Neplatný kód. Zkontrolujte čas na vašem zařízení.');
            return;
        }

        $this->userRepository->update($userId, [
            'two_factor_verified' => true,
        ]);

        $this->flashMessage('Dvoufázové ověření bylo úspěšně aktivováno.', 'success');
        $this->redirect('Profile:security');
    }

    /**
     * Redirect to OAuth provider to link account
     */
    public function actionLinkAccount(string $provider): void
    {
        if (!$this->oauthService->isProviderSupported($provider)) {
            $this->flashMessage('Nepodporovaný poskytovatel.', 'error');
            $this->redirect('Profile:');
        }

        $session = $this->getSession()->getSection('oauth');
        $session->set('linking', true);
        $session->set('link_user_id', $this->getUser()->getId());

        try {
            $url = $this->oauthService->getAuthorizationUrl($provider);
            $this->redirectUrl($url);
        } catch (OAuthException $e) {
            $this->flashMessage('Chyba při propojování: ' . $e->getMessage(), 'error');
            $this->redirect('Profile:');
        }
    }

    /**
     * Handle OAuth callback for account linking
     */
    public function actionLinkCallback(): void
    {
        $code = $this->getParameter('code');
        $state = $this->getParameter('state');
        $error = $this->getParameter('error');

        $session = $this->getSession()->getSection('oauth');
        $provider = $session->get('provider');
        $isLinking = $session->get('linking');
        $linkUserId = $session->get('link_user_id');

        $session->remove('linking');
        $session->remove('link_user_id');

        if ($error) {
            $this->flashMessage('Propojení bylo zrušeno.', 'warning');
            $this->redirect('Profile:');
        }

        if (!$isLinking || !$linkUserId || !$provider || !$code || !$state) {
            $this->flashMessage('Neplatná odpověď.', 'error');
            $this->redirect('Profile:');
        }

        try {
            $oauthUser = $this->oauthService->handleCallback($provider, $code, $state);

            $existingUser = $this->userRepository->findByOAuthProvider($provider, $oauthUser->providerId);
            if ($existingUser && $existingUser->id !== $linkUserId) {
                $this->flashMessage('Tento účet je již propojen s jiným uživatelem.', 'error');
                $this->redirect('Profile:');
            }

            $this->userRepository->linkOAuthAccount($linkUserId, $provider, $oauthUser->providerId);

            $providerName = match($provider) {
                'google' => 'Google',
                'facebook' => 'Facebook',
                'discord' => 'Discord',
                default => $provider,
            };

            $this->flashMessage("Účet {$providerName} byl úspěšně propojen.", 'success');
            $this->redirect('Profile:');
        } catch (OAuthException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect('Profile:');
        }
    }

    /**
     * Unlink OAuth account from user profile
     */
    public function actionUnlinkAccount(string $provider): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        // Check if provider is valid
        $column = match ($provider) {
            'google' => 'google_id',
            'facebook' => 'facebook_id',
            'discord' => 'discord_id',
            default => null,
        };

        if ($column === null) {
            $this->flashMessage('Nepodporovaný poskytovatel.', 'error');
            $this->redirect('Profile:');
        }

        // Check if the account is actually linked
        if (empty($userData->$column)) {
            $this->flashMessage('Tento účet není propojen.', 'warning');
            $this->redirect('Profile:');
        }

        // Check if user has another way to log in
        $hasPassword = !empty($userData->password_hash);
        $linkedAccounts = 0;
        if (!empty($userData->google_id)) $linkedAccounts++;
        if (!empty($userData->facebook_id)) $linkedAccounts++;
        if (!empty($userData->discord_id)) $linkedAccounts++;

        // User must have password OR at least 2 linked accounts to unlink one
        if (!$hasPassword && $linkedAccounts <= 1) {
            $this->flashMessage('Nelze odpojit poslední způsob přihlášení. Nejdřív si nastavte heslo.', 'error');
            $this->redirect('Profile:');
        }

        // Unlink the account
        $this->userRepository->update($userId, [$column => null]);

        $providerName = match ($provider) {
            'google' => 'Google',
            'facebook' => 'Facebook',
            'discord' => 'Discord',
            default => $provider,
        };

        $this->flashMessage("Účet {$providerName} byl úspěšně odpojen.", 'success');
        $this->redirect('Profile:');
    }

    protected function createComponentProfileForm(): Form
    {
        $form = new Form;

        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $form->addText('first_name', 'Jméno')
            ->setRequired('Zadejte jméno')
            ->setDefaultValue($userData->first_name);

        $form->addText('last_name', 'Příjmení')
            ->setRequired('Zadejte příjmení')
            ->setDefaultValue($userData->last_name);

        $form->addText('nickname', 'Přezdívka')
            ->setNullable()
            ->setMaxLength(50)
            ->setDefaultValue($userData->nickname ?? null);

        $form->addText('phone', 'Telefon')
            ->setNullable()
            ->setDefaultValue($userData->phone);

        $form->addSubmit('save', 'Uložit změny');

        $form->onSuccess[] = [$this, 'profileFormSucceeded'];

        return $form;
    }

    public function profileFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();

        $this->userRepository->update($userId, [
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'nickname' => $data->nickname,
            'phone' => $data->phone,
        ]);

        $this->flashMessage('Profil byl úspěšně aktualizován.', 'success');
        $this->redirect('this');
    }

    /**
     * Formulář pro nastavení hesla (OAuth uživatelé)
     */
    protected function createComponentSetPasswordForm(): Form
    {
        $form = new Form;

        $form->addPassword('password', 'Nové heslo')
            ->setRequired('Zadejte heslo')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků', 8);

        $form->addPassword('password_confirm', 'Potvrzení hesla')
            ->setRequired('Potvrďte heslo')
            ->addRule($form::Equal, 'Hesla se neshodují', $form['password']);

        $form->addSubmit('save', 'Nastavit heslo');

        $form->onSuccess[] = [$this, 'setPasswordFormSucceeded'];

        return $form;
    }

    public function setPasswordFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();

        $this->userRepository->update($userId, [
            'password_hash' => password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $this->flashMessage('Heslo bylo úspěšně nastaveno. Nyní se můžete přihlásit i pomocí e-mailu a hesla.', 'success');
        $this->redirect('Profile:security');
    }

    /**
     * Formulář pro změnu hesla (uživatelé s heslem)
     */
    protected function createComponentChangePasswordForm(): Form
    {
        $form = new Form;

        $form->addPassword('current_password', 'Současné heslo')
            ->setRequired('Zadejte současné heslo');

        $form->addPassword('password', 'Nové heslo')
            ->setRequired('Zadejte nové heslo')
            ->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků', 8);

        $form->addPassword('password_confirm', 'Potvrzení hesla')
            ->setRequired('Potvrďte heslo')
            ->addRule($form::Equal, 'Hesla se neshodují', $form['password']);

        $form->addSubmit('save', 'Změnit heslo');

        $form->onSuccess[] = [$this, 'changePasswordFormSucceeded'];

        return $form;
    }

    public function changePasswordFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        if (!password_verify($data->current_password, $userData->password_hash)) {
            $form->addError('Současné heslo není správné.');
            return;
        }

        $this->userRepository->update($userId, [
            'password_hash' => password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
        $this->redirect('Profile:security');
    }
}
