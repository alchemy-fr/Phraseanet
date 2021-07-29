<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Compression extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Compression';

    protected $FullName = 'mixed';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Compression';

    protected $flag_Unsafe = true;

    protected $flag_Mandatory = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => 'Uncompressed',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'CCITT 1D',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'T4/Group 3 Fax',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'T6/Group 4 Fax',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'LZW',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'JPEG (old-style)',
        ),
        6 => array(
            'Id' => 7,
            'Label' => 'JPEG',
        ),
        7 => array(
            'Id' => 8,
            'Label' => 'Adobe Deflate',
        ),
        8 => array(
            'Id' => 9,
            'Label' => 'JBIG B&W',
        ),
        9 => array(
            'Id' => 10,
            'Label' => 'JBIG Color',
        ),
        10 => array(
            'Id' => 99,
            'Label' => 'JPEG',
        ),
        11 => array(
            'Id' => 262,
            'Label' => 'Kodak 262',
        ),
        12 => array(
            'Id' => 32766,
            'Label' => 'Next',
        ),
        13 => array(
            'Id' => 32767,
            'Label' => 'Sony ARW Compressed',
        ),
        14 => array(
            'Id' => 32769,
            'Label' => 'Packed RAW',
        ),
        15 => array(
            'Id' => 32770,
            'Label' => 'Samsung SRW Compressed',
        ),
        16 => array(
            'Id' => 32771,
            'Label' => 'CCIRLEW',
        ),
        17 => array(
            'Id' => 32772,
            'Label' => 'Samsung SRW Compressed 2',
        ),
        18 => array(
            'Id' => 32773,
            'Label' => 'PackBits',
        ),
        19 => array(
            'Id' => 32809,
            'Label' => 'Thunderscan',
        ),
        20 => array(
            'Id' => 32867,
            'Label' => 'Kodak KDC Compressed',
        ),
        21 => array(
            'Id' => 32895,
            'Label' => 'IT8CTPAD',
        ),
        22 => array(
            'Id' => 32896,
            'Label' => 'IT8LW',
        ),
        23 => array(
            'Id' => 32897,
            'Label' => 'IT8MP',
        ),
        24 => array(
            'Id' => 32898,
            'Label' => 'IT8BL',
        ),
        25 => array(
            'Id' => 32908,
            'Label' => 'PixarFilm',
        ),
        26 => array(
            'Id' => 32909,
            'Label' => 'PixarLog',
        ),
        27 => array(
            'Id' => 32946,
            'Label' => 'Deflate',
        ),
        28 => array(
            'Id' => 32947,
            'Label' => 'DCS',
        ),
        29 => array(
            'Id' => 33003,
            'Label' => 'Aperio JPEG 2000 YCbCr',
        ),
        30 => array(
            'Id' => 33005,
            'Label' => 'Aperio JPEG 2000 RGB',
        ),
        31 => array(
            'Id' => 34661,
            'Label' => 'JBIG',
        ),
        32 => array(
            'Id' => 34676,
            'Label' => 'SGILog',
        ),
        33 => array(
            'Id' => 34677,
            'Label' => 'SGILog24',
        ),
        34 => array(
            'Id' => 34712,
            'Label' => 'JPEG 2000',
        ),
        35 => array(
            'Id' => 34713,
            'Label' => 'Nikon NEF Compressed',
        ),
        36 => array(
            'Id' => 34715,
            'Label' => 'JBIG2 TIFF FX',
        ),
        37 => array(
            'Id' => 34718,
            'Label' => 'Microsoft Document Imaging (MDI) Binary Level Codec',
        ),
        38 => array(
            'Id' => 34719,
            'Label' => 'Microsoft Document Imaging (MDI) Progressive Transform Codec',
        ),
        39 => array(
            'Id' => 34720,
            'Label' => 'Microsoft Document Imaging (MDI) Vector',
        ),
        40 => array(
            'Id' => 34887,
            'Label' => 'ESRI Lerc',
        ),
        41 => array(
            'Id' => 34892,
            'Label' => 'Lossy JPEG',
        ),
        42 => array(
            'Id' => 34925,
            'Label' => 'LZMA2',
        ),
        43 => array(
            'Id' => 34926,
            'Label' => 'Zstd',
        ),
        44 => array(
            'Id' => 34927,
            'Label' => 'WebP',
        ),
        45 => array(
            'Id' => 34933,
            'Label' => 'PNG',
        ),
        46 => array(
            'Id' => 34934,
            'Label' => 'JPEG XR',
        ),
        47 => array(
            'Id' => 65000,
            'Label' => 'Kodak DCR Compressed',
        ),
        48 => array(
            'Id' => 65535,
            'Label' => 'Pentax PEF Compressed',
        ),
        49 => array(
            'Id' => 34316,
            'Label' => 'Panasonic RAW 1',
        ),
        50 => array(
            'Id' => 34826,
            'Label' => 'Panasonic RAW 2',
        ),
        51 => array(
            'Id' => 34828,
            'Label' => 'Panasonic RAW 3',
        ),
        52 => array(
            'Id' => 34830,
            'Label' => 'Panasonic RAW 4',
        ),
    );

}
