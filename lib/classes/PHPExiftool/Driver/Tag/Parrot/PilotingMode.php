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
class PilotingMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PilotingMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'mixed';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Piloting Mode';

    protected $local_g2 = 'Device';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Return Home',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Flight Plan',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Follow Me',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Return Home',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Flight Plan',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'Follow Me / Tracking',
        ),
        8 => array(
            'Id' => 4,
            'Label' => 'Magic Carpet',
        ),
        9 => array(
            'Id' => 5,
            'Label' => 'Move To',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Manual',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Return Home',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Flight Plan',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Follow Me / Tracking',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'Magic Carpet',
        ),
        15 => array(
            'Id' => 5,
            'Label' => 'Move To',
        ),
    );

}
