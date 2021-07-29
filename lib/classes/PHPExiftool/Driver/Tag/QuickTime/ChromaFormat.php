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
class ChromaFormat extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ChromaFormat';

    protected $FullName = 'mixed';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Chroma Format';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'YUV 4:4:4',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'YUV 4:2:2',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'YUV 4:2:0',
        ),
        3 => array(
            'Id' => 7,
            'Label' => 'Monochrome 4:0:0',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Monochrome',
        ),
        5 => array(
            'Id' => 1,
            'Label' => '4:2:0',
        ),
        6 => array(
            'Id' => 2,
            'Label' => '4:2:2',
        ),
        7 => array(
            'Id' => 3,
            'Label' => '4:4:4',
        ),
    );

}
