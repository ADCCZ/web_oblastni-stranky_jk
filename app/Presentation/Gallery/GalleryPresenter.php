<?php

declare(strict_types=1);

namespace App\Presentation\Gallery;

use App\Presentation\BasePresenter;

final class GalleryPresenter extends BasePresenter
{
    public function renderDefault(): void
    {
        // TODO: Load galleries from repository
        $this->template->galleries = [];
    }
}
