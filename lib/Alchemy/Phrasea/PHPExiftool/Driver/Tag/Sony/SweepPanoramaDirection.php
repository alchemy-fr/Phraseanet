<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Sony;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class SweepPanoramaDirection extends AbstractTag
{

    protected $Id = 51;

    protected $Name = 'SweepPanoramaDirection';

    protected $FullName = 'Sony::CameraSettings3';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Sweep Panorama Direction';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Right',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Up',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Down',
        ),
    );

}
