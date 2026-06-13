<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Map;

/**
 * Kuratiertes, kartenrelevantes Icon-Set (Maki von Mapbox, CC0/Public Domain) unter public/icons/.
 * Reihenfolge = Anzeigereihenfolge im Backend-Picker. Lesbare Bezeichnungen liegen als
 * Sprach-Strings in MSC.maplibreIcons (de/en).
 */
final class IconCatalog
{
    public const PUBLIC_PATH = 'bundles/mandraelcontaomaplibre/icons';

    public const ICONS = [
        'marker',
        'school',
        'college',
        'library',
        'building',
        'town-hall',
        'home',
        'hospital',
        'doctor',
        'pharmacy',
        'place-of-worship',
        'lodging',
        'cafe',
        'restaurant',
        'theatre',
        'music',
        'art-gallery',
        'shop',
        'parking',
        'information',
        'star',
        'heart',
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return self::ICONS;
    }

    public static function has(string $key): bool
    {
        return '' !== $key && \in_array($key, self::ICONS, true);
    }

    /**
     * Web-Pfad (relativ zum Bundle-Asset-Verzeichnis) zur Icon-SVG.
     */
    public static function relativeUrl(string $key): string
    {
        return self::PUBLIC_PATH.'/'.$key.'.svg';
    }
}
