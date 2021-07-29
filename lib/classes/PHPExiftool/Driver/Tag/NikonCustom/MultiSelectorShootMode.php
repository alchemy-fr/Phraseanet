<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MultiSelectorShootMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MultiSelectorShootMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Multi Selector Shoot Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Highlight Active Focus Point',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Not Used',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        5 => array(
            'Id' => 3,
            'Label' => 'Not Used (None)',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Zoom On/Off',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Not Used (None)',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        12 => array(
            'Id' => 3,
            'Label' => 'Highlight Active Focus Point',
        ),
        13 => array(
            'Id' => 4,
            'Label' => 'Not Used (None)',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Highlight Active Focus Point',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Not Used',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        18 => array(
            'Id' => 1,
            'Label' => 'Highlight Active Focus Point',
        ),
        19 => array(
            'Id' => 2,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Not Used (None)',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        22 => array(
            'Id' => 2,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        23 => array(
            'Id' => 3,
            'Label' => 'Highlight Active Focus Point',
        ),
        24 => array(
            'Id' => 4,
            'Label' => 'Not Used (None)',
        ),
    );

}
