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
class PixelFormat extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'PixelFormat';

    protected $FullName = 'BPG::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Pixel Format';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Grayscale',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '4:2:0 (chroma at 0.5, 0.5)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '4:2:2 (chroma at 0.5, 0)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '4:4:4',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '4:2:0 (chroma at 0, 0.5)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '4:2:2 (chroma at 0, 0)',
        ),
    );

}
