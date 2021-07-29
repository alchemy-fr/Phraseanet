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
class ShutterReleaseButtonAEL extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ShutterReleaseButtonAE-L';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Shutter Release Button AE-L';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'On (Half Press)',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'On (Burst Mode)',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'On (Half Press)',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'On (Burst Mode)',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        21 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'On (Half Press)',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 'On (Burst Mode)',
        ),
        25 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        26 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

}
