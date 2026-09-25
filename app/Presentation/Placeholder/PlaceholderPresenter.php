<?php

declare(strict_types=1);

namespace App\Presentation\Placeholder;

use App\Presentation\BasePresenter;

final class PlaceholderPresenter extends BasePresenter
{
    public function renderDefault(string $title = 'Tato stránka'): void
    {
        $this->template->pageTitle = $title;
    }
}
