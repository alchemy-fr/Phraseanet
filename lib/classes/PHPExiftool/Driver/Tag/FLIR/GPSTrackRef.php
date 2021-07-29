<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLIR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSTrackRef extends AbstractTag
{

    protected $Id = 70;

    protected $Name = 'GPSTrackRef';

    protected $FullName = 'FLIR::GPSInfo';

    protected $GroupName = 'FLIR';

    protected $g0 = 'APP1';

    protected $g1 = 'FLIR';

    protected $g2 = 'Location';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'GPS Track Ref';

    protected $MaxLength = 2;

    protected $Values = array(
        'M' => array(
            'Id' => 'M',
            'Label' => 'Magnetic North',
        ),
        'T' => array(
            'Id' => 'T',
            'Label' => 'True North',
        ),
    );

}
