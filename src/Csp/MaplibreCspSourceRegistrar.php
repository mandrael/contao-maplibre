<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Csp;

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;

/**
 * Trägt die von MapLibre + OpenFreeMap benötigten Hosts in die Seiten-CSP ein, falls die Seite eine
 * CSP nutzt. Nur auf Contao 5.x aktiv (native CSP-API); der Service wird von der Extension nur dort
 * registriert. addSource() ergänzt nur, wenn die Direktive bereits eine Source-Liste hat – also nur,
 * wenn die Seite tatsächlich eine CSP gesetzt hat.
 */
final class MaplibreCspSourceRegistrar
{
    public function __construct(private readonly ResponseContextAccessor $responseContextAccessor)
    {
    }

    public function register(): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (null === $responseContext || !$responseContext->has(CspHandler::class)) {
            return;
        }

        $csp = $responseContext->get(CspHandler::class);

        // MapLibre GL JS + CSS von unpkg.
        $csp->addSource('script-src', 'https://unpkg.com');
        $csp->addSource('style-src', 'https://unpkg.com');

        // OpenFreeMap: Style-JSON, Vektor-Tiles, Glyphs und Sprites werden per fetch geladen.
        $csp->addSource('connect-src', 'https://tiles.openfreemap.org');

        // MapLibre rendert Tiles in ein Canvas und nutzt Web-Worker via Blob-URLs.
        $csp->addSource('img-src', 'data:');
        $csp->addSource('img-src', 'blob:');
        $csp->addSource('worker-src', 'blob:');
    }
}
