<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Map;

/**
 * Ein einzelner Karten-Marker. Bewusst transport-agnostisch (Werte-Objekt), damit auch
 * fremde Bundles (z. B. ein kuenftiger Anwender-Katalog) Marker an den Renderer uebergeben koennen.
 */
final class Marker
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
        public readonly string $title = '',
        public readonly string $address = '',
        public readonly string $link = '',
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
    ) {
    }

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        $data = ['lat' => $this->lat, 'lng' => $this->lng];

        if ('' !== $this->title) {
            $data['title'] = $this->title;
        }

        if ('' !== $this->address) {
            $data['address'] = $this->address;
        }

        if ('' !== $this->link) {
            $data['link'] = $this->link;
        }

        if (null !== $this->color && '' !== $this->color) {
            $data['color'] = $this->color;
        }

        if (null !== $this->icon && '' !== $this->icon) {
            $data['icon'] = $this->icon;
        }

        return $data;
    }

    public function isValid(): bool
    {
        // [0,0] liegt im Atlantik – fuer unsere Zwecke ein sicheres "ungesetzt".
        return !(0.0 === $this->lat && 0.0 === $this->lng);
    }
}
