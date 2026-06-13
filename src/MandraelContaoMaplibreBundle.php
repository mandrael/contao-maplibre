<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

// Klassische Bundle-Basis statt AbstractBundle: AbstractBundle existiert erst ab
// Symfony 6.1, Contao 4.13 laeuft aber auf Symfony 5.4.
class MandraelContaoMaplibreBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
