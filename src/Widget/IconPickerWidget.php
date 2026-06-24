<?php

declare(strict_types=1);

namespace Mandrael\ContaoMaplibreBundle\Widget;

use Contao\StringUtil;
use Contao\Widget;
use Mandrael\ContaoMaplibreBundle\Map\IconCatalog;

/**
 * Visueller Icon-Picker fürs Backend (anklickbares Raster mit Icon-Vorschau, wie Google My Maps).
 * Speichert den gewählten Icon-Key (oder leer = Standard-Pin) in einem versteckten Feld.
 * Registriert als $GLOBALS['BE_FFL']['maplibreIconPicker'].
 */
class IconPickerWidget extends Widget
{
    protected $strTemplate = 'be_widget';

    /**
     * @var bool
     */
    protected $blnSubmitInput = true;

    public function validate(): void
    {
        $value = (string) $this->getPost($this->strName);

        if (!IconCatalog::has($value)) {
            $value = '';
        }

        $this->varValue = $value;
    }

    public function generate(): string
    {
        $value = (string) $this->varValue;
        $iconBase = IconCatalog::PUBLIC_PATH;
        $labels = $GLOBALS['TL_LANG']['MSC']['maplibreIcons'] ?? [];
        $noneLabel = $GLOBALS['TL_LANG']['MSC']['maplibreIconNone'] ?? 'Standard-Pin';

        $options = $this->renderOption('', '' === $value, '', $noneLabel, true);

        foreach (IconCatalog::keys() as $key) {
            $label = (string) ($labels[$key] ?? $key);
            $options .= $this->renderOption($key, $key === $value, $iconBase.'/'.$key.'.svg', $label, false);
        }

        $ctrlId = 'ctrl_'.$this->strId;

        return $this->styleBlock().sprintf(
            '<div class="maplibre-iconpicker" data-target="%s">%s</div>'
            .'<input type="hidden" name="%s" id="%s" value="%s">',
            $ctrlId,
            $options,
            $this->strName,
            $ctrlId,
            StringUtil::specialchars($value)
        ).$this->scriptBlock($ctrlId);
    }

    private function renderOption(string $key, bool $selected, string $iconUrl, string $label, bool $isNone): string
    {
        $inner = $isNone
            ? '<span class="mlip-none">&#9678;</span>'
            : sprintf('<img src="%s" alt="" loading="lazy">', StringUtil::specialchars($iconUrl));

        return sprintf(
            '<button type="button" class="mlip-opt%s" data-val="%s" title="%s">%s<span class="mlip-label">%s</span></button>',
            $selected ? ' selected' : '',
            StringUtil::specialchars($key),
            StringUtil::specialchars($label),
            $inner,
            StringUtil::specialchars($label)
        );
    }

    private function styleBlock(): string
    {
        return '<style>'
            .'.maplibre-iconpicker{display:flex;flex-wrap:wrap;gap:6px;margin:4px 0}'
            .'.maplibre-iconpicker .mlip-opt{display:flex;flex-direction:column;align-items:center;justify-content:flex-start;'
            .'width:72px;padding:8px 4px;border:1px solid var(--form-border);border-radius:6px;background:var(--form-bg);cursor:pointer;font-size:10px;color:var(--text)}'
            .'.maplibre-iconpicker .mlip-opt:hover{border-color:var(--green)}'
            .'.maplibre-iconpicker .mlip-opt.selected{border-color:var(--green);box-shadow:0 0 0 1px var(--green)}'
            .'.maplibre-iconpicker .mlip-opt img{width:22px;height:22px;margin-bottom:5px}'
            .'.maplibre-iconpicker .mlip-none{font-size:22px;line-height:22px;height:22px;margin-bottom:5px;color:var(--gray)}'
            .'.maplibre-iconpicker .mlip-label{text-align:center;line-height:1.2;word-break:break-word}'
            .'</style>';
    }

    private function scriptBlock(string $ctrlId): string
    {
        return '<script>(function(){'
            .'var box=document.querySelector(\'.maplibre-iconpicker[data-target="'.$ctrlId.'"]\');'
            .'if(!box)return;var input=document.getElementById("'.$ctrlId.'");'
            .'box.addEventListener("click",function(e){var b=e.target.closest(".mlip-opt");if(!b)return;e.preventDefault();'
            .'input.value=b.getAttribute("data-val");'
            .'box.querySelectorAll(".mlip-opt").forEach(function(o){o.classList.remove("selected")});'
            .'b.classList.add("selected");});'
            .'})();</script>';
    }
}
