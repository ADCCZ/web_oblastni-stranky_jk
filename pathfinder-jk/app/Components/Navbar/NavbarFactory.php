<?php

declare(strict_types=1);

namespace App\Components\Navbar;

interface NavbarFactory
{
    public function create(): Navbar;
}
