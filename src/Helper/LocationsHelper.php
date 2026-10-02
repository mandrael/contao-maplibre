<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Helper;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * DB-Zugriffe rund um die zentralen Standorte: Options-Callbacks für die DCA-Felder und die
 * Frontend-Abfrage der veröffentlichten Standorte (nach Auswahl und/oder Kategorie).
 */
final class LocationsHelper
{
    private const PUBLISHED = "published = '1'"
        ." AND (start = '' OR start <= :now)"
        ." AND (stop = '' OR stop > :now)"
        ." AND latitude != '' AND longitude != ''";

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
            $label = self::plain((string) $row['title']);
            $city = self::plain((string) ($row['city'] ?? ''));

            if ('' !== $city) {
                $label .= ' ('.$city.')';
            }

            // Contao gibt Options-Labels im Select-Widget ungefiltert aus – deshalb hier maskieren.
            $options[(int) $row['id']] = self::escape($label);
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

        $options = [];

        // Wert im Klartext (passt zu Alt- und Neubestand), Label maskiert (Contao gibt es ungefiltert aus).
        foreach ($rows as $row) {
            $category = self::category((string) $row);
            $options[$category] = self::escape($category);
        }

        return $options;
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
     * Kategorie-Name als Schlüssel für Auswahl, Vergleich, Farbe und Legende: Klartext ohne spitze Klammern.
     * Der Name landet als Wert im Select des Karten-Elements; Contao gibt einen nicht mehr vorhandenen Wert
     * ("unknown option") ungefiltert aus. Ohne "<" und ">" kann dort kein HTML entstehen.
     */
    public static function category(string $value): string
    {
        return trim(str_replace(['<', '>'], '', self::plain($value)));
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

        $params = ['now' => time()];
        $types = ['now' => ParameterType::INTEGER];

        // Kategorien im Klartext vergleichen: Altbestand kann HTML-kodiert gespeichert sein ("Essen &amp; Café").
        // Daher erst nur id/category lesen und in PHP zuordnen, dann die Treffer vollständig per ID laden.
        if ($categories) {
            $categories = array_map(self::category(...), $categories);
            $rows = $this->connection->fetchAllAssociative('SELECT id, category FROM tl_maplibre_location WHERE '.self::PUBLISHED, $params, $types);

            foreach ($rows as $row) {
                if (\in_array(self::category((string) $row['category']), $categories, true)) {
                    $ids[] = (int) $row['id'];
                }
            }
        }

        if (!$ids) {
            return [];
        }

        $params['ids'] = array_values(array_unique($ids));
        $types['ids'] = ArrayParameterType::INTEGER;

        return $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_maplibre_location WHERE id IN (:ids) AND '.self::PUBLISHED.' ORDER BY sorting, title',
            $params,
            $types,
        );
    }
}
