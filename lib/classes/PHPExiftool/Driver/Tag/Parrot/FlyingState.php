<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Parrot;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlyingState extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlyingState';

    protected $FullName = 'mixed';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'mixed';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Flying State';

    protected $local_g2 = 'Device';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Landed',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Taking Off',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Hovering',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Flying',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Landing',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Emergency',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Landed',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Taking Off',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Hovering',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'Flying',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'Landing',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'Emergency',
        ),
        12 => array(
            'Id' => 6,
            'Label' => 'User Takeoff',
        ),
        13 => array(
            'Id' => 7,
            'Label' => 'Motor Ramping',
        ),
        14 => array(
            'Id' => 8,
            'Label' => 'Emergency Landing',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Landed',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Taking Off',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Hovering',
        ),
        18 => array(
            'Id' => 3,
            'Label' => 'Flying',
        ),
        19 => array(
            'Id' => 4,
            'Label' => 'Landing',
        ),
        20 => array(
            'Id' => 5,
            'Label' => 'Emergency',
        ),
        21 => array(
            'Id' => 6,
            'Label' => 'User Takeoff',
        ),
        22 => array(
            'Id' => 7,
            'Label' => 'Motor Ramping',
        ),
        23 => array(
            'Id' => 8,
            'Label' => 'Emergency Landing',
        ),
    );

}
