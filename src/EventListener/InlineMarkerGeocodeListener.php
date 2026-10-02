<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\EventListener;

use Contao\ContentModel;
use Contao\DataContainer;
use Contao\Message;
use Contao\StringUtil;
use Mandrael\ContaoMaplibreBundle\Service\GeocodingService;

/**
 * onsubmit_callback für tl_content (Typ maplibre_map): parst die Ad-hoc-Marker-Textarea
 * ("Bezeichnung; Adresse" je Zeile), geocodiert neue/geänderte Adressen einmalig und cacht die
 * Koordinaten serialisiert in maplibre_inline_cache. So fällt beim Rendern kein Geocoding an.
 */
final class InlineMarkerGeocodeListener
{
    public function __construct(private readonly GeocodingService $geocoder)
    {
    }

    public function onSubmit(DataContainer $dc): void
    {
        $id = (int) ($dc->id ?? 0);

        if (0 === $id) {
            return;
        }

        $model = ContentModel::findByPk($id);

        if (null === $model || 'maplibre_map' !== $model->type) {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $model->maplibre_inline) ?: [];

        $cached = [];

        foreach (StringUtil::deserialize($model->maplibre_inline_cache, true) as $entry) {
            if (isset($entry['address'])) {
                $cached[$this->key((string) $entry['address'])] = $entry;
            }
        }

        $result = [];
        $failed = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' === $line) {
                continue;
            }

            [$title, $address] = $this->split($line);

            if ('' === $address) {
                $failed[] = $line;
                continue;
            }

            $key = $this->key($address);

            if (isset($cached[$key]['lat'], $cached[$key]['lng'])) {
                $result[] = ['title' => $title, 'address' => $address, 'lat' => $cached[$key]['lat'], 'lng' => $cached[$key]['lng']];
                continue;
            }

            // Nominatim-Policy (max. 1 Anfrage/Sekunde): drosselt zentral in GeocodingService.
            $coords = $this->geocoder->geocode($address);

            if (null === $coords) {
                $failed[] = $address;
                continue;
            }

            $result[] = ['title' => $title, 'address' => $address, 'lat' => $coords['lat'], 'lng' => $coords['lng']];
        }

        $model->maplibre_inline_cache = serialize($result);
        $model->save();

        if ($failed) {
            Message::addError(sprintf(
                $GLOBALS['TL_LANG']['tl_content']['maplibreGeocodeError'] ?? 'Geocoding fehlgeschlagen für: %s',
                htmlspecialchars(implode('; ', $failed), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ));
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function split(string $line): array
    {
        foreach ([';', '|'] as $separator) {
            if (str_contains($line, $separator)) {
                $parts = explode($separator, $line, 2);

                return [trim($parts[0]), trim($parts[1])];
            }
        }

        return ['', $line];
    }

    private function key(string $address): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $address)));
    }
}
