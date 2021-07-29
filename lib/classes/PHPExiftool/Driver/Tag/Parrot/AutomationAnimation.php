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
class AutomationAnimation extends AbstractTag
{

    protected $Id = 28;

    protected $Name = 'AutomationAnimation';

    protected $FullName = 'Parrot::Automation';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'Location';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Automation Animation';

    protected $local_g2 = 'Device';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Orbit',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Boomerang',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Parabola',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Dolly Slide',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Dolly Zoom',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Reveal Vertical',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Reveal Horizontal',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Candle',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Flip Front',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Flip Back',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Flip Left',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Flip Right',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Twist Up',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Position Twist Up',
        ),
    );

}
