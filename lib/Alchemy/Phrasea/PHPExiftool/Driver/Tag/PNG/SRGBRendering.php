<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\PNG;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class SRGBRendering extends AbstractTag
{

    protected $Id = 'sRGB';

    protected $Name = 'SRGBRendering';

    protected $FullName = 'PNG::Main';

    protected $GroupName = 'PNG';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'SRGB Rendering';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Perceptual',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Relative Colorimetric',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Saturation',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Absolute Colorimetric',
        ),
    );

}
