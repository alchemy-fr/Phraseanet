<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SubIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DistortionCorrection extends AbstractTag
{

    protected $Id = 28726;

    protected $Name = 'DistortionCorrection';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'SubIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Distortion Correction';

    protected $local_g1 = 'SubIFD';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Auto fixed by lens',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'No correction params available',
        ),
    );

}
