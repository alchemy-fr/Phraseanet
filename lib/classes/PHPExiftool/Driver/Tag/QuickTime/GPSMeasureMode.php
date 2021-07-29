<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSMeasureMode extends AbstractTag
{

    protected $Id = 12;

    protected $Name = 'GPSMeasureMode';

    protected $FullName = 'QuickTime::camm6';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Location';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'GPS Measure Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Measurement',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2-Dimensional Measurement',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3-Dimensional Measurement',
        ),
    );

}
