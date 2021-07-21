<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\H264;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RecordingMode extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'RecordingMode';

    protected $FullName = 'H264::RecInfo';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Recording Mode';

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'XP+',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'SP',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'LP',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'FXP',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'MXP',
        ),
    );

}
