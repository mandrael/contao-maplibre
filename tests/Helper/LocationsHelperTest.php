<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Tests\Helper;

use Doctrine\DBAL\Connection;
use Mandrael\ContaoMaplibreBundle\Helper\LocationsHelper;
use PHPUnit\Framework\TestCase;

class LocationsHelperTest extends TestCase
{
    public function testPlainDecodesWhatContaoEncodesOnSave(): void
    {
        $this->assertSame('Essen & Café', LocationsHelper::plain('Essen &amp; Café'));
        $this->assertSame('#b5532b', LocationsHelper::plain('&#35;b5532b'));
        $this->assertSame('Hotel & Gasthof', LocationsHelper::plain(' Hotel & Gasthof '));
    }

    public function testCategoryOptionsUsePlainValuesAndEscapedLabels(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchFirstColumn')->willReturn(['Essen &amp; Café', 'Essen & Café', 'X &#60;img src=x onerror=alert(1)>']);

        $options = (new LocationsHelper($connection))->getCategories();

        // Alt- (kodiert) und Neubestand (Klartext) ergeben eine Option; das Label enthält kein ausführbares HTML.
        $this->assertSame([
            'Essen & Café' => 'Essen &amp; Café',
            'X <img src=x onerror=alert(1)>' => 'X &lt;img src=x onerror=alert(1)&gt;',
        ], $options);
    }

    public function testLocationOptionsAreEscaped(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([['id' => 3, 'title' => '<b>Hof</b> & Garten', 'city' => 'Wien']]);

        $this->assertSame([3 => '&lt;b&gt;Hof&lt;/b&gt; &amp; Garten (Wien)'], (new LocationsHelper($connection))->getLocations());
    }

    public function testFindPublishedMatchesEncodedAndPlainCategoriesThenLoadsById(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(2))->method('fetchAllAssociative')->willReturnCallback(
            function (string $sql, array $params): array {
                if (str_starts_with($sql, 'SELECT id, category')) {
                    return [
                        ['id' => 1, 'category' => 'Essen &amp; Café'],
                        ['id' => 2, 'category' => 'Essen & Café'],
                        ['id' => 3, 'category' => 'Kursorte'],
                    ];
                }

                $this->assertStringContainsString('id IN (:ids)', $sql);
                $this->assertSame([7, 1, 2], $params['ids']);

                return [['id' => 1], ['id' => 2], ['id' => 7]];
            },
        );

        $rows = (new LocationsHelper($connection))->findPublished([7], ['Essen & Café']);

        $this->assertCount(3, $rows);
    }

    public function testFindPublishedWithoutSelectionQueriesNothing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('fetchAllAssociative');

        $this->assertSame([], (new LocationsHelper($connection))->findPublished([], []));
    }
}
