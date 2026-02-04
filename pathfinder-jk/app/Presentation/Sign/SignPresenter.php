<?php

declare(strict_types=1);

namespace App\Presentation\Sign;

use App\Forms\SignInFormFactory;
use App\Forms\SignUpFormFactory;
use App\Model\Repository\TwoFactorRepository;
use App\Model\Repository\UserRepository;
use App\Presentation\BasePresenter;
use App\Security\OAuthService;
use App\Security\OAuthAuthenticator;
use App\Security\OAuthException;
use App\Services\MailService;
use App\Services\TotpService;
use Nette\Application\UI\Form;
use Nette\Security\SimpleIdentity;

final class SignPresenter extends BasePresenter
{
    public function __construct(
        private SignInFormFactory $signInFormFactory,
        private SignUpFormFactory $signUpFormFactory,
        private OAuthService $oauthService,
        private OAuthAuthenticator $oauthAuthenticator,
        private UserRepository $userRepository,
        private TwoFactorRepository $twoFactorRepository,
        private MailService $mailService,
        private TotpService $totpService,
    ) {
    }

    /**
     * Sign-in form factory
     */
    protected function createComponentSignInForm(): Form
    {
        return $this->signInFormFactory->create(
            // onSuccess - přihlášení bez 2FA
            function (): void {
                $this->flashMessage('Byli jste úspěšně přihlášeni.', 'success');
                $this->redirect('Home:');
            },
            // onTwoFactorRequired - vyžaduje 2FA
            function (int $userId, string $twoFactorType, bool $remember): void {
                // Ulož do session
                $session = $this->getSession()->getSection('two_factor_login');
                $session->set('user_id', $userId);
                $session->set('two_factor_type', $twoFactorType);
                $session->set('remember', $remember);
                $session->setExpiration('10 minutes');

                // Pokud je typ email, odešli kód
                if ($twoFactorType === 'email') {
                    $user = $this->userRepository->findById($userId);
                    $code = $this->twoFactorRepository->createCode($userId, 'email');

                    try {
                        $this->mailService->sendTwoFactorCode($user->email, $code, $user->first_name);
                    } catch (\Exception $e) {
                        $this->flashMessage('Nepodařilo se odeslat ověřovací kód.', 'error');
                        $this->redirect('Sign:in');
                        return;
                    }
                }

                $this->redirect('Sign:twoFactor');
            }
        );
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
     * 2FA verification form
     */
    protected function createComponentTwoFactorForm(): Form
    {
        $form = new Form;

        $form->addText('code', 'Ověřovací kód')
            ->setRequired('Zadejte 6-místný kód')
            ->addRule($form::Pattern, 'Kód musí mít 6 číslic', '[0-9]{6}');

        $form->addSubmit('verify', 'Ověřit');

        $form->onSuccess[] = [$this, 'twoFactorFormSucceeded'];

        return $form;
    }

    public function twoFactorFormSucceeded(Form $form, \stdClass $data): void
    {
        $session = $this->getSession()->getSection('two_factor_login');
        $userId = $session->get('user_id');
        $twoFactorType = $session->get('two_factor_type');
        $remember = $session->get('remember');

        if (!$userId || !$twoFactorType) {
            $this->flashMessage('Session vypršela. Přihlaste se znovu.', 'error');
            $this->redirect('Sign:in');
            return;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            $this->redirect('Sign:in');
            return;
        }

        // Ověř kód podle typu
        $verified = false;
        if ($twoFactorType === 'totp') {
            $verified = $this->totpService->verify($user->two_factor_secret, $data->code);
        } elseif ($twoFactorType === 'email') {
            $verified = $this->twoFactorRepository->verifyCode($userId, $data->code, 'email');
        }

        if (!$verified) {
            $form->addError('Neplatný nebo expirovaný kód.');
            return;
        }

        // Vymaž session
        $session->remove('user_id');
        $session->remove('two_factor_type');
        $session->remove('remember');

        // Přihlaš uživatele
        $this->getUser()->setExpiration($remember ? '14 days' : '20 minutes');
        $identity = new SimpleIdentity(
            $user->id,
            $user->role,
            [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]
        );
        $this->getUser()->login($identity);

        $this->flashMessage('Byli jste úspěšně přihlášeni.', 'success');
        $this->redirect('Home:');
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
     * Two-factor verification page
     */
    public function actionTwoFactor(): void
    {
        $session = $this->getSession()->getSection('two_factor_login');
        $userId = $session->get('user_id');

        if (!$userId) {
            $this->redirect('Sign:in');
        }
    }

    public function renderTwoFactor(): void
    {
        $session = $this->getSession()->getSection('two_factor_login');
        $twoFactorType = $session->get('two_factor_type');
        $userId = $session->get('user_id');

        $user = $this->userRepository->findById($userId);

        $this->template->twoFactorType = $twoFactorType;
        $this->template->userEmail = $user ? $user->email : '';
    }

    /**
     * Resend 2FA code (for email type)
     */
    public function handleResendCode(): void
    {
        $session = $this->getSession()->getSection('two_factor_login');
        $userId = $session->get('user_id');
        $twoFactorType = $session->get('two_factor_type');

        if (!$userId || $twoFactorType !== 'email') {
            $this->redirect('Sign:in');
            return;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            $this->redirect('Sign:in');
            return;
        }

        $code = $this->twoFactorRepository->createCode($userId, 'email');

        try {
            $this->mailService->sendTwoFactorCode($user->email, $code, $user->first_name);
            $this->flashMessage('Nový kód byl odeslán.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Nepodařilo se odeslat kód.', 'error');
        }

        $this->redirect('this');
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

    /**
     * Redirect to OAuth provider
     */
    public function actionOauth(string $provider): void
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Home:');
        }

        if (!$this->oauthService->isProviderSupported($provider)) {
            $this->flashMessage('Nepodporovaný způsob přihlášení.', 'error');
            $this->redirect('Sign:in');
        }

        try {
            $url = $this->oauthService->getAuthorizationUrl($provider);
            $this->redirectUrl($url);
        } catch (OAuthException $e) {
            $this->flashMessage('Chyba při přihlašování: ' . $e->getMessage(), 'error');
            $this->redirect('Sign:in');
        }
    }

    /**
     * Handle OAuth callback from provider
     */
    public function actionOauthCallback(): void
    {
        $code = $this->getParameter('code');
        $state = $this->getParameter('state');
        $error = $this->getParameter('error');

        $section = $this->getSession()->getSection('oauth');
        $provider = $section->get('provider');
        $isLinking = $section->get('linking');

        // If this is account linking, redirect to Profile handler
        if ($isLinking) {
            $this->redirect('Profile:linkCallback', [
                'code' => $code,
                'state' => $state,
                'error' => $error,
            ]);
        }

        if ($error) {
            $this->flashMessage('Přihlášení bylo zrušeno.', 'warning');
            $this->redirect('Sign:in');
        }

        if (!$provider || !$code || !$state) {
            $this->flashMessage('Neplatná odpověď od poskytovatele.', 'error');
            $this->redirect('Sign:in');
        }

        try {
            $oauthUser = $this->oauthService->handleCallback($provider, $code, $state);

            if (empty($oauthUser->email)) {
                $this->flashMessage('Nepodařilo se získat e-mailovou adresu. Zkuste jiný způsob přihlášení.', 'error');
                $this->redirect('Sign:in');
            }

            $identity = $this->oauthAuthenticator->authenticate($oauthUser);

            // Zkontroluj 2FA pro OAuth uživatele
            $user = $this->userRepository->findById($identity->getId());
            $twoFactorType = $user->two_factor_type ?? 'none';
            $twoFactorVerified = (bool) ($user->two_factor_verified ?? false);

            if ($twoFactorType !== 'none' && $twoFactorVerified) {
                // Vyžaduje 2FA
                $session = $this->getSession()->getSection('two_factor_login');
                $session->set('user_id', $user->id);
                $session->set('two_factor_type', $twoFactorType);
                $session->set('remember', true);
                $session->setExpiration('10 minutes');

                if ($twoFactorType === 'email') {
                    $code = $this->twoFactorRepository->createCode($user->id, 'email');
                    try {
                        $this->mailService->sendTwoFactorCode($user->email, $code, $user->first_name);
                    } catch (\Exception $e) {
                        // Ignoruj chybu, uživatel může požádat o nový kód
                    }
                }

                $this->redirect('Sign:twoFactor');
            }

            $this->getUser()->login($identity);

            $this->flashMessage('Byli jste úspěšně přihlášeni.', 'success');
            $this->redirect('Home:');
        } catch (OAuthException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect('Sign:in');
        } catch (\Exception $e) {
            $this->flashMessage('Nastala neočekávaná chyba při přihlašování.', 'error');
            $this->redirect('Sign:in');
        }
    }
}
