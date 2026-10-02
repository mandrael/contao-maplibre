<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * DB-Zugriffe rund um die zentralen Standorte: Options-Callbacks für die DCA-Felder und die
 * Frontend-Abfrage der veröffentlichten Standorte (nach Auswahl und/oder Kategorie).
 */
final class LocationsHelper
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<int, string>
     */
    public function getLocations(): array
    {
        $options = [];

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, title, city FROM tl_maplibre_location ORDER BY title'
        );

        foreach ($rows as $row) {
            $label = (string) $row['title'];
            $city = trim((string) ($row['city'] ?? ''));

            if ('' !== $city) {
                $label .= ' ('.$city.')';
            }

            $options[(int) $row['id']] = $label;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function getCategories(): array
    {
        $rows = $this->connection->fetchFirstColumn(
            "SELECT DISTINCT category FROM tl_maplibre_location WHERE category != '' ORDER BY category"
        );

        $categories = array_values(array_unique(array_map(self::plain(...), $rows)));

        return array_combine($categories, $categories) ?: [];
    }

    /**
     * Contao speichert Textfelder ohne decodeEntities HTML-kodiert (z. B. "&amp;", "&#35;"). Für Vergleiche
     * und die Ausgabe (JSON, textContent) wird der Klartext gebraucht – auch für Altbestand.
     */
    public static function plain(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Liefert die veröffentlichten Standorte mit gültigen Koordinaten – per ID-Auswahl und/oder Kategorie.
     *
     * @param array<int|string> $ids
     * @param array<string> $categories
     *
     * @return array<array<string, mixed>>
     */
    public function findPublished(array $ids, array $categories): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        $categories = array_values(array_filter(array_map('strval', $categories), static fn (string $c): bool => '' !== $c));

        if (!$ids && !$categories) {
            return [];
        }

        // Kategorien im Klartext vergleichen: Altbestand kann HTML-kodiert gespeichert sein ("Essen &amp; Café").
        $categories = array_map(self::plain(...), $categories);

        $sql = 'SELECT * FROM tl_maplibre_location'
            ." WHERE published = '1'"
            ." AND (start = '' OR start <= :now)"
            ." AND (stop = '' OR stop > :now)"
            ." AND latitude != '' AND longitude != ''"
            .' ORDER BY sorting, title';

        $rows = $this->connection->fetchAllAssociative($sql, ['now' => time()], ['now' => ParameterType::INTEGER]);

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => \in_array((int) $row['id'], $ids, true)
                || \in_array(self::plain((string) $row['category']), $categories, true),
        ));
    }
}
