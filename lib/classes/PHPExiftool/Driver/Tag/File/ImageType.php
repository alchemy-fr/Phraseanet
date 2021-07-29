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
class ImageType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'ImageType';

    protected $FullName = 'FLIF::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Image Type';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Grayscale (non-interlaced)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'RGB (non-interlaced)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'RGBA (non-interlaced)',
        ),
        'A' => array(
            'Id' => 'A',
            'Label' => 'Grayscale (interlaced)',
        ),
        'C' => array(
            'Id' => 'C',
            'Label' => 'RGB (interlaced)',
        ),
        'D' => array(
            'Id' => 'D',
            'Label' => 'RGBA (interlaced)',
        ),
        'Q' => array(
            'Id' => 'Q',
            'Label' => 'Grayscale Animation (non-interlaced)',
        ),
        'S' => array(
            'Id' => 'S',
            'Label' => 'RGB Animation (non-interlaced)',
        ),
        'T' => array(
            'Id' => 'T',
            'Label' => 'RGBA Animation (non-interlaced)',
        ),
        'a' => array(
            'Id' => 'a',
            'Label' => 'Grayscale Animation (interlaced)',
        ),
        'c' => array(
            'Id' => 'c',
            'Label' => 'RGB Animation (interlaced)',
        ),
        'd' => array(
            'Id' => 'd',
            'Label' => 'RGBA Animation (interlaced)',
        ),
    );

}
