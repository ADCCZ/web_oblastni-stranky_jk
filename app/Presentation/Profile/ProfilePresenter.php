<?php

declare(strict_types=1);

namespace App\Presentation\Profile;

use App\Model\Repository\ChildRepository;
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
        private ChildRepository $childRepository,
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

        // Zkontroluj jestli už byl kód odeslán (aby se neposílal znovu při refreshi nebo chybě formuláře)
        $session = $this->getSession()->getSection('2fa_email_setup');
        $codeSent = $session->get('code_sent');

        if (!$codeSent) {
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
                $session->set('code_sent', true);
                $session->setExpiration('10 minutes');
            } catch (\Exception $e) {
                $this->flashMessage('Nepodařilo se odeslat ověřovací kód.', 'error');
                $this->redirect('Profile:security');
            }
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

        // Vymaž session flag
        $session = $this->getSession()->getSection('2fa_email_setup');
        $session->remove('code_sent');

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

        // Vygeneruj nový kód (starý se tím zneplatní)
        $code = $this->twoFactorRepository->createCode($userId, 'email');

        // Obnov session flag
        $session = $this->getSession()->getSection('2fa_email_setup');
        $session->set('code_sent', true);
        $session->setExpiration('10 minutes');

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

        $form->addEmail('email', 'E-mail')
            ->setRequired('Zadejte e-mail')
            ->setDefaultValue($userData->email);

        $form->addText('nickname', 'Přezdívka')
            ->setNullable()
            ->setMaxLength(50)
            ->setDefaultValue($userData->nickname ?? null);

        $form->addText('phone', 'Telefon')
            ->setNullable()
            ->setDefaultValue($userData->phone);

        $form->addCheckbox('newsletter', 'Chci dostávat novinky e-mailem')
            ->setDefaultValue((bool) $userData->newsletter);

        $form->addSubmit('save', 'Uložit změny');

        $form->onSuccess[] = [$this, 'profileFormSucceeded'];

        return $form;
    }

    public function profileFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        // Kontrola unikátnosti emailu při změně
        if ($data->email !== $userData->email && $this->userRepository->emailExists($data->email)) {
            $form->addError('Uživatel s tímto e-mailem již existuje.');
            return;
        }

        $this->userRepository->update($userId, [
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'email' => $data->email,
            'nickname' => $data->nickname,
            'phone' => $data->phone,
            'newsletter' => $data->newsletter ? 1 : 0,
        ]);

        $this->flashMessage('Profil byl úspěšně aktualizován.', 'success');
        $this->redirect('this');
    }

    // ===== Rozsireny profil =====

    protected function createComponentExtendedProfileForm(): Form
    {
        $form = new Form;
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $form->addText('street', 'Ulice a č.p.')
            ->setNullable()
            ->setDefaultValue($userData->street);

        $form->addText('city', 'Město')
            ->setNullable()
            ->setDefaultValue($userData->city);

        $form->addText('zip', 'PSČ')
            ->setNullable()
            ->setMaxLength(10)
            ->setDefaultValue($userData->zip);

        $form->addText('birth_date', 'Datum narození')
            ->setNullable()
            ->setHtmlType('date')
            ->setDefaultValue($userData->birth_date ? $userData->birth_date->format('Y-m-d') : null);

        $form->addCheckbox('is_pathfinder_member', 'Registrován v Klubu Pathfinder')
            ->setDefaultValue((bool) $userData->is_pathfinder_member);

        $form->addCheckbox('is_vegetarian', 'Vegetarián')
            ->setDefaultValue((bool) $userData->is_vegetarian);

        $form->addText('dietary_notes', 'Stravovací návyky')
            ->setNullable()
            ->setDefaultValue($userData->dietary_notes);

        $form->addText('allergies', 'Alergie')
            ->setNullable()
            ->setDefaultValue($userData->allergies);

        $form->addText('health_notes', 'Zdravotní požadavky')
            ->setNullable()
            ->setDefaultValue($userData->health_notes);

        $form->addText('medications', 'Léky')
            ->setNullable()
            ->setDefaultValue($userData->medications);

        $form->addSelect('can_swim', 'Umí plavat', ['' => '-- Nevyplněno --', '1' => 'Ano', '0' => 'Ne'])
            ->setDefaultValue($userData->can_swim === null ? '' : ($userData->can_swim ? '1' : '0'));

        $form->addText('clothing_size', 'Velikost oblečení')
            ->setNullable()
            ->setMaxLength(10)
            ->setDefaultValue($userData->clothing_size);

        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = [$this, 'extendedProfileFormSucceeded'];

        return $form;
    }

    public function extendedProfileFormSucceeded(Form $form, \stdClass $data): void
    {
        $userId = $this->getUser()->getId();

        $this->userRepository->update($userId, [
            'street' => $data->street ?: null,
            'city' => $data->city ?: null,
            'zip' => $data->zip ?: null,
            'birth_date' => $data->birth_date ?: null,
            'is_pathfinder_member' => $data->is_pathfinder_member ? 1 : 0,
            'is_vegetarian' => $data->is_vegetarian ? 1 : 0,
            'dietary_notes' => $data->dietary_notes ?: null,
            'allergies' => $data->allergies ?: null,
            'health_notes' => $data->health_notes ?: null,
            'medications' => $data->medications ?: null,
            'can_swim' => $data->can_swim === '' ? null : (int) $data->can_swim,
            'clothing_size' => $data->clothing_size ?: null,
        ]);

        $this->flashMessage('Rozšířené údaje byly uloženy.', 'success');
        $this->redirect('this');
    }

    // ===== Deti =====

    public function renderChildren(): void
    {
        $userId = $this->getUser()->getId();
        $userData = $this->userRepository->findById($userId);

        $this->template->profile = $userData;
        $this->template->children = $this->childRepository->findByParent($userId)->fetchAll();
    }

    public function handleSaveChild(): void
    {
        $this->getHttpResponse()->setContentType('application/json');

        if (!$this->getUser()->isLoggedIn()) {
            $this->sendJson(['success' => false, 'errors' => ['Musíte být přihlášeni.']]);
        }

        $rawInput = file_get_contents('php://input');
        if (!$rawInput) {
            $this->sendJson(['success' => false, 'errors' => ['Prázdný požadavek.']]);
        }

        try {
            $data = \Nette\Utils\Json::decode($rawInput, forceArrays: true);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'errors' => ['Neplatný formát dat.']]);
        }

        $errors = [];
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');

        if ($firstName === '') {
            $errors[] = 'Jméno je povinné.';
        }
        if ($lastName === '') {
            $errors[] = 'Příjmení je povinné.';
        }

        if (!empty($errors)) {
            $this->sendJson(['success' => false, 'errors' => $errors]);
        }

        $userId = $this->getUser()->getId();
        $childId = isset($data['id']) ? (int) $data['id'] : 0;

        $childData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'street' => !empty($data['street']) ? trim($data['street']) : null,
            'city' => !empty($data['city']) ? trim($data['city']) : null,
            'zip' => !empty($data['zip']) ? trim($data['zip']) : null,
            'is_pathfinder_member' => !empty($data['is_pathfinder_member']) ? 1 : 0,
            'is_vegetarian' => !empty($data['is_vegetarian']) ? 1 : 0,
            'dietary_notes' => !empty($data['dietary_notes']) ? trim($data['dietary_notes']) : null,
            'allergies' => !empty($data['allergies']) ? trim($data['allergies']) : null,
            'health_notes' => !empty($data['health_notes']) ? trim($data['health_notes']) : null,
            'medications' => !empty($data['medications']) ? trim($data['medications']) : null,
            'can_swim' => isset($data['can_swim']) && $data['can_swim'] !== '' ? (int) $data['can_swim'] : null,
            'clothing_size' => !empty($data['clothing_size']) ? trim($data['clothing_size']) : null,
        ];

        try {
            if ($childId > 0) {
                if (!$this->childRepository->belongsToParent($childId, $userId)) {
                    $this->sendJson(['success' => false, 'errors' => ['Dítě nebylo nalezeno.']]);
                }
                $this->childRepository->update($childId, $childData);
                $this->sendJson(['success' => true, 'message' => 'Údaje dítěte byly aktualizovány.']);
            } else {
                $childData['parent_id'] = $userId;
                $child = $this->childRepository->create($childData);
                $this->sendJson(['success' => true, 'message' => 'Dítě bylo přidáno.', 'childId' => $child->id]);
            }
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'errors' => ['Chyba při ukládání.']]);
        }
    }

    public function handleDeleteChild(int $childId): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $userId = $this->getUser()->getId();
        if (!$this->childRepository->belongsToParent($childId, $userId)) {
            $this->flashMessage('Dítě nebylo nalezeno.', 'error');
            $this->redirect('this');
            return;
        }

        $this->childRepository->delete($childId);
        $this->flashMessage('Dítě bylo odebráno.', 'success');
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
