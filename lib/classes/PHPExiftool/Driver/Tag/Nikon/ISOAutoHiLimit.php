<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ISOAutoHiLimit extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ISOAutoHiLimit';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'ISO Auto Hi Limit';

    protected $flag_Permanent = true;

    protected $Values = array(
        36 => array(
            'Id' => 36,
            'Label' => 'ISO 200',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'ISO 250',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'ISO 280',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'ISO 320',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'ISO 400',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'ISO 500',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'ISO 560',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'ISO 640',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'ISO 800',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'ISO 1000',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'ISO 1100',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'ISO 1250',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'ISO 1600',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'ISO 2000',
        ),
        57 => array(
            'Id' => 57,
            'Label' => 'ISO 2200',
        ),
        58 => array(
            'Id' => 58,
            'Label' => 'ISO 2500',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'ISO 3200',
        ),
        62 => array(
            'Id' => 62,
            'Label' => 'ISO 4000',
        ),
        63 => array(
            'Id' => 63,
            'Label' => 'ISO 4500',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'ISO 5000',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'ISO 6400',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'ISO 8000',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'ISO 9000',
        ),
        70 => array(
            'Id' => 70,
            'Label' => 'ISO 10000',
        ),
        72 => array(
            'Id' => 72,
            'Label' => 'ISO 12800',
        ),
        74 => array(
            'Id' => 74,
            'Label' => 'ISO 16000',
        ),
        75 => array(
            'Id' => 75,
            'Label' => 'ISO 18000',
        ),
        76 => array(
            'Id' => 76,
            'Label' => 'ISO 20000',
        ),
        78 => array(
            'Id' => 78,
            'Label' => 'ISO 25600',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'ISO 32000',
        ),
        81 => array(
            'Id' => 81,
            'Label' => 'ISO 36000',
        ),
        82 => array(
            'Id' => 82,
            'Label' => 'ISO 40000',
        ),
        84 => array(
            'Id' => 84,
            'Label' => 'ISO 51200',
        ),
        86 => array(
            'Id' => 86,
            'Label' => 'ISO Hi 0.3',
        ),
        87 => array(
            'Id' => 87,
            'Label' => 'ISO Hi 0.5',
        ),
        88 => array(
            'Id' => 88,
            'Label' => 'ISO Hi 0.7',
        ),
        90 => array(
            'Id' => 90,
            'Label' => 'ISO Hi 1.0',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'ISO Hi 2.0',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'ISO Hi 3.0',
        ),
        108 => array(
            'Id' => 108,
            'Label' => 'ISO Hi 4.0',
        ),
        114 => array(
            'Id' => 114,
            'Label' => 'ISO Hi 5.0',
        ),
    );

}
