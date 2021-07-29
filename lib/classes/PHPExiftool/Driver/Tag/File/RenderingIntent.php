<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RenderingIntent extends AbstractTag
{

    protected $Id = 108;

    protected $Name = 'RenderingIntent';

    protected $FullName = 'BMP::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Rendering Intent';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Graphic (LCS_GM_BUSINESS)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Proof (LCS_GM_GRAPHICS)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Picture (LCS_GM_IMAGES)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Absolute Colorimetric (LCS_GM_ABS_COLORIMETRIC)',
        ),
    );

}
