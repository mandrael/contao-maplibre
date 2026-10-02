<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Map;

/**
 * Ein einzelner Karten-Marker. Bewusst transport-agnostisch (Werte-Objekt), damit auch
 * fremde Bundles (z. B. ein kuenftiger Anwender-Katalog) Marker an den Renderer uebergeben koennen.
 */
final class Marker
{
    public readonly string $link;

    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
        public readonly string $title = '',
        public readonly string $address = '',
        string $link = '',
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
        public readonly string $description = '',
        public readonly string $category = '',
    ) {
        // An dieser einen Stelle bereinigt, damit toArray() (Inhaltselement + öffentliche
        // Renderer-API) nie ein gefährliches Schema (javascript:/data:/vbscript: ...) ausliefert.
        $this->link = self::sanitizeLink($link);
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

        if ('' !== $this->description) {
            $data['description'] = $this->description;
        }

        if ('' !== $this->category) {
            $data['category'] = $this->category;
        }

        return $data;
    }

    public function isValid(): bool
    {
        if (!is_finite($this->lat) || !is_finite($this->lng)) {
            return false;
        }

        if ($this->lat < -90.0 || $this->lat > 90.0 || $this->lng < -180.0 || $this->lng > 180.0) {
            return false;
        }

        // [0,0] liegt im Atlantik – fuer unsere Zwecke ein sicheres "ungesetzt".
        return !(0.0 === $this->lat && 0.0 === $this->lng);
    }

    /**
     * Erlaubt: leer, http(s)/mailto/tel (Schema case-insensitiv) sowie schemalose relative URLs
     * (beginnend mit "/" ohne "//", "#" oder "?", oder ohne Doppelpunkt vor dem ersten /, ? oder #).
     * Alles andere (javascript:, data:, vbscript:, protokoll-relative "//..." ...) wird verworfen.
     */
    private static function sanitizeLink(string $link): string
    {
        $link = trim($link);

        if ('' === $link) {
            return '';
        }

        // Browser lesen "\" wie "/" und streichen Steuerzeichen: "\\evil.example" oder "\x01//evil"
        // würden sonst zum Link auf eine fremde Domain.
        if (preg_match('/[\\\\\x00-\x1F\x7F]/', $link)) {
            return '';
        }

        if (preg_match('/^(https?|mailto|tel):/i', $link)) {
            return $link;
        }

        // "//evil.example" ist ein protokoll-relativer Link auf eine fremde Domain, kein erlaubtes Schema.
        if (str_starts_with($link, '//')) {
            return '';
        }

        $stop = strcspn($link, '/?#:');

        if ($stop === \strlen($link) || ':' !== $link[$stop]) {
            return $link;
        }

        return '';
    }
}
