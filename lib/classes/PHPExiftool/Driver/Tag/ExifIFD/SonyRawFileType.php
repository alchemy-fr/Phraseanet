<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ExifIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SonyRawFileType extends AbstractTag
{

    protected $Id = 28672;

    protected $Name = 'SonyRawFileType';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'ExifIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Sony Raw File Type';

    protected $local_g1 = 'ExifIFD';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Sony Uncompressed 14-bit RAW',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Sony Uncompressed 12-bit RAW',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Sony Compressed RAW',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Sony Lossless Compressed RAW',
        ),
    );

}
