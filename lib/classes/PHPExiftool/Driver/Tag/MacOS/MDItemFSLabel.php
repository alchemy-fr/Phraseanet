<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MacOS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MDItemFSLabel extends AbstractTag
{

    protected $Id = 'MDItemFSLabel';

    protected $Name = 'MDItemFSLabel';

    protected $FullName = 'MacOS::MDItem';

    protected $GroupName = 'MacOS';

    protected $g0 = 'File';

    protected $g1 = 'MacOS';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'MD Item FS Label';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0 (none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1 (Gray)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2 (Green)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3 (Purple)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '4 (Blue)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '5 (Yellow)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '6 (Red)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '7 (Orange)',
        ),
    );

}
