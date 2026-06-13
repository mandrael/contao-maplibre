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

        return array_combine($rows, $rows) ?: [];
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

        $conditions = [];
        $params = [];
        $types = [];

        if ($ids) {
            $conditions[] = 'id IN (:ids)';
            $params['ids'] = $ids;
            $types['ids'] = ArrayParameterType::INTEGER;
        }

        if ($categories) {
            $conditions[] = 'category IN (:cats)';
            $params['cats'] = $categories;
            $types['cats'] = ArrayParameterType::STRING;
        }

        $params['now'] = time();
        $types['now'] = ParameterType::INTEGER;

        $sql = 'SELECT * FROM tl_maplibre_location'
            .' WHERE ('.implode(' OR ', $conditions).')'
            ." AND published = '1'"
            ." AND (start = '' OR start <= :now)"
            ." AND (stop = '' OR stop > :now)"
            ." AND latitude != '' AND longitude != ''"
            .' ORDER BY sorting, title';

        return $this->connection->fetchAllAssociative($sql, $params, $types);
    }
}
