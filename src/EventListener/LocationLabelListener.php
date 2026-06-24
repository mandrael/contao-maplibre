<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\EventListener;

use Contao\DataContainer;
use Contao\StringUtil;

/**
 * label_callback für die Backend-Liste tl_maplibre_location: Titel + Ort und ein Status-Punkt,
 * ob für den Standort bereits Koordinaten vorliegen (grün) oder fehlen (rot).
 */
final class LocationLabelListener
{
    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $args
     */
    public function __invoke(array $row, string $label, ?DataContainer $dc = null, array $args = []): string
    {
        $title = StringUtil::specialchars((string) ($row['title'] ?? ''));
        $city = trim((string) ($row['city'] ?? ''));
        $suffix = '' !== $city ? ' <span class="tl_gray">– '.StringUtil::specialchars($city).'</span>' : '';

        $hasCoords = '' !== (string) ($row['latitude'] ?? '') && '' !== (string) ($row['longitude'] ?? '');

        $dot = $hasCoords
            ? '<span title="Koordinaten vorhanden" class="tl_green">&#9679;</span>'
            : '<span title="Keine Koordinaten" class="tl_red">&#9679;</span>';

        return $dot.' '.$title.$suffix;
    }
}
