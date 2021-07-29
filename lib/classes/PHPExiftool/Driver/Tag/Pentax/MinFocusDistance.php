<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MinFocusDistance extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'MinFocusDistance';

    protected $FullName = 'Pentax::LensData';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Min Focus Distance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0.13-0.19 m',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '0.20-0.24 m',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '0.25-0.28 m',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '0.28-0.30 m',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '0.35-0.38 m',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '0.40-0.45 m',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '0.49-0.50 m',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '0.6 m',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '0.7 m',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '0.8-0.9 m',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '1.0 m',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '1.1-1.2 m',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '1.4-1.5 m',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '1.5 m',
        ),
        14 => array(
            'Id' => 14,
            'Label' => '2.0 m',
        ),
        15 => array(
            'Id' => 15,
            'Label' => '2.0-2.1 m',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '2.1 m',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '2.2-2.9 m',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '3.0 m',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '4-5 m',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '5.6 m',
        ),
    );

}
