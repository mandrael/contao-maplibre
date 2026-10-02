<?php

use Contao\DC_Table;
use Mandrael\ContaoMaplibreBundle\EventListener\LocationGeocodeListener;
use Mandrael\ContaoMaplibreBundle\EventListener\LocationLabelListener;

$GLOBALS['TL_DCA']['tl_maplibre_location'] = [
    'config' => [
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            [LocationGeocodeListener::class, 'onSubmit'],
        ],
        'sql' => [
            'keys' => [
                'id'       => 'primary',
                'category' => 'index',
                'published' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode'        => 2,
            'fields'      => ['title'],
            'flag'        => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields'         => ['title', 'city'],
            'format'         => '%s',
            'label_callback' => [LocationLabelListener::class, '__invoke'],
        ],
        'global_operations' => [
            'all' => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'href'  => 'act=toggle&amp;field=published',
                'icon'  => 'visible.svg',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        'default' => '{location_legend},title,category,description;{address_legend},street,postal,city,country,maplibre_regeocode;{coords_legend},latitude,longitude;{marker_legend},icon,iconSvg;{link_legend},link;{publish_legend},published,start,stop',
    ],

    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'category' => [
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql'       => "varchar(128) NOT NULL default ''",
        ],
        'description' => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => ['decodeEntities' => true, 'tl_class' => 'clr', 'style' => 'height:80px'],
            'sql'       => 'text NULL',
        ],
        'street' => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'tl_class' => 'clr long', 'decodeEntities' => true],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'postal' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'city' => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql'       => "varchar(128) NOT NULL default ''",
        ],
        'country' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'maplibre_regeocode' => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50 m12'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        // Fingerabdruck der zuletzt geocodierten Adresse (kein Feld in der Palette): erkennt eine
        // geänderte Adresse, ohne manuell gesetzte Koordinaten bei leerem Fingerabdruck anzutasten.
        'maplibre_geocoded_address' => [
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'latitude' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'longitude' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'icon' => [
            'exclude'   => true,
            'inputType' => 'maplibreIconPicker',
            'eval'      => ['tl_class' => 'clr'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'iconSvg' => [
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'svg', 'tl_class' => 'clr'],
            'sql'       => 'binary(16) NULL',
        ],
        'link' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'url', 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'published' => [
            'exclude'   => true,
            'filter'    => true,
            'toggle'    => true,
            'inputType' => 'checkbox',
            'eval'      => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
    ],
];
