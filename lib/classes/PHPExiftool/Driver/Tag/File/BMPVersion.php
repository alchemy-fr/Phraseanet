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
class BMPVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'BMPVersion';

    protected $FullName = 'mixed';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'BMP Version';

    protected $Values = array(
        0 => array(
            'Id' => 40,
            'Label' => 'Windows V3',
        ),
        1 => array(
            'Id' => 68,
            'Label' => 'AVI BMP structure?',
        ),
        2 => array(
            'Id' => 108,
            'Label' => 'Windows V4',
        ),
        3 => array(
            'Id' => 124,
            'Label' => 'Windows V5',
        ),
        4 => array(
            'Id' => 12,
            'Label' => 'OS/2 V1',
        ),
        5 => array(
            'Id' => 64,
            'Label' => 'OS/2 V2',
        ),
    );

}
