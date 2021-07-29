<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class JPEGQuality extends AbstractTag
{

    protected $Id = 67;

    protected $Name = 'JPEGQuality';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'JPEG Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a (Movie)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Standard',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Very High',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'n/a (RAW only)',
        ),
    );

}
