<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Sony;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Quality2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Quality2';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Quality 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'JPEG',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'RAW',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RAW + JPEG',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'JPEG',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'RAW',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'RAW + JPEG',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'JPEG + MPO',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'JPEG',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'RAW',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'RAW + JPEG',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'JPEG + MPO',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'JPEG',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'RAW',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'RAW + JPEG',
        ),
        14 => array(
            'Id' => 3,
            'Label' => 'JPEG + MPO',
        ),
    );

}
