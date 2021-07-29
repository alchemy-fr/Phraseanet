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
class Stereoscopic3D extends AbstractTag
{

    protected $Id = 'st3d';

    protected $Name = 'Stereoscopic3D';

    protected $FullName = 'QuickTime::ImageDesc';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Stereoscopic 3D';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Monoscopic',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Stereoscopic Top-Bottom',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Stereoscopic Left-Right',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Stereoscopic Stereo-Custom',
        ),
    );

}
