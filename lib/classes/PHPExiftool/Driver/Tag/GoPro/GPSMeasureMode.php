<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GoPro;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSMeasureMode extends AbstractTag
{

    protected $Id = 'GPSF';

    protected $Name = 'GPSMeasureMode';

    protected $FullName = 'GoPro::GPMF';

    protected $GroupName = 'GoPro';

    protected $g0 = 'GoPro';

    protected $g1 = 'GoPro';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Measure Mode';

    protected $Values = array(
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
