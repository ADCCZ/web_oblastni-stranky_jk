<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Model\Repository\UserRepository;
use App\Presentation\BasePresenter;

final class AdminPresenter extends BasePresenter
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage('Pro přístup do administrace se musíte přihlásit.', 'warning');
            $this->redirect('Sign:in');
        }

        if ($this->getUser()->getIdentity()->getRoles()[0] !== 'admin') {
            $this->flashMessage('Nemáte oprávnění k přístupu do administrace.', 'error');
            $this->redirect('Home:');
        }
    }

    public function renderDefault(): void
    {
        $this->template->users = $this->userRepository->getAll();
        $this->template->currentUserId = $this->getUser()->getId();
    }

    public function renderDetail(int $id): void
    {
        $userData = $this->userRepository->findById($id);
        if (!$userData) {
            $this->flashMessage('Uživatel nebyl nalezen.', 'error');
            $this->redirect('Admin:');
        }

        $this->template->detail = $userData;
        $this->template->currentUserId = $this->getUser()->getId();
    }

    /**
     * Změna role uživatele (AJAX signal)
     */
    public function handleChangeRole(int $userId, string $role): void
    {
        // Nelze měnit vlastní roli
        if ($userId === $this->getUser()->getId()) {
            $this->flashMessage('Nemůžete měnit vlastní roli.', 'error');
            $this->redirect('this');
            return;
        }

        // Povolené role (admin nelze přidělit)
        if (!in_array($role, ['member', 'parent', 'leader'], true)) {
            $this->flashMessage('Neplatná role.', 'error');
            $this->redirect('this');
            return;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            $this->flashMessage('Uživatel nebyl nalezen.', 'error');
            $this->redirect('this');
            return;
        }

        // Nelze měnit roli jinému adminovi
        if ($user->role === 'admin') {
            $this->flashMessage('Nelze měnit roli administrátora.', 'error');
            $this->redirect('this');
            return;
        }

        $this->userRepository->update($userId, ['role' => $role]);

        $roleNames = ['leader' => 'Vedoucí', 'parent' => 'Rodič', 'member' => 'Člen'];
        $roleName = $roleNames[$role] ?? $role;
        $this->flashMessage("Role uživatele {$user->first_name} {$user->last_name} byla změněna na: {$roleName}.", 'success');
        $this->redirect('this');
    }

    /**
     * Aktivace/deaktivace uživatele
     */
    public function handleToggleActive(int $userId): void
    {
        if ($userId === $this->getUser()->getId()) {
            $this->flashMessage('Nemůžete deaktivovat vlastní účet.', 'error');
            $this->redirect('this');
            return;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            $this->flashMessage('Uživatel nebyl nalezen.', 'error');
            $this->redirect('this');
            return;
        }

        if ($user->role === 'admin') {
            $this->flashMessage('Nelze deaktivovat administrátora.', 'error');
            $this->redirect('this');
            return;
        }

        $newState = !$user->is_active;
        $this->userRepository->update($userId, ['is_active' => $newState]);

        $action = $newState ? 'aktivován' : 'deaktivován';
        $this->flashMessage("Uživatel {$user->first_name} {$user->last_name} byl {$action}.", 'success');
        $this->redirect('this');
    }
}
