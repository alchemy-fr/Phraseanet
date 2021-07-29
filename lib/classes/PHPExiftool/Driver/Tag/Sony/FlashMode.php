<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Rear Sync',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Wireless',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'Fill-flash',
        ),
        4 => array(
            'Id' => 5,
            'Label' => 'Flash Off',
        ),
        5 => array(
            'Id' => 6,
            'Label' => 'Slow Sync',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Flash Off',
        ),
        7 => array(
            'Id' => 16,
            'Label' => 'Autoflash',
        ),
        8 => array(
            'Id' => 17,
            'Label' => 'Fill-flash',
        ),
        9 => array(
            'Id' => 18,
            'Label' => 'Slow Sync',
        ),
        10 => array(
            'Id' => 19,
            'Label' => 'Rear Sync',
        ),
        11 => array(
            'Id' => 20,
            'Label' => 'Wireless',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Flash Off',
        ),
        13 => array(
            'Id' => 16,
            'Label' => 'Autoflash',
        ),
        14 => array(
            'Id' => 17,
            'Label' => 'Fill-flash',
        ),
        15 => array(
            'Id' => 18,
            'Label' => 'Slow Sync',
        ),
        16 => array(
            'Id' => 19,
            'Label' => 'Rear Sync',
        ),
        17 => array(
            'Id' => 20,
            'Label' => 'Wireless',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        21 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        22 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        23 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        26 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        27 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        28 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        29 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        30 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        31 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        32 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        33 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        34 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        35 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        36 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        37 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        38 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        39 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        40 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        41 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        42 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        43 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        44 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        45 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        46 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        47 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        48 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        49 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        50 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        51 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        52 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        53 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        54 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        55 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        56 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        57 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        58 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        59 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        60 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        61 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        62 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        63 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        64 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        65 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
        66 => array(
            'Id' => 0,
            'Label' => 'Autoflash',
        ),
        67 => array(
            'Id' => 1,
            'Label' => 'Fill-flash',
        ),
        68 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        69 => array(
            'Id' => 3,
            'Label' => 'Slow Sync',
        ),
        70 => array(
            'Id' => 4,
            'Label' => 'Rear Sync',
        ),
        71 => array(
            'Id' => 6,
            'Label' => 'Wireless',
        ),
    );

}
