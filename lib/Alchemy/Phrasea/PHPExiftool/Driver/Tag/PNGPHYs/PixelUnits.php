<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\PNGPHYs;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PixelUnits extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'PixelUnits';

    protected $FullName = 'PNG::PhysicalPixel';

    protected $GroupName = 'PNG-pHYs';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG-pHYs';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Pixel Units';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'meters',
        ),
    );

}
