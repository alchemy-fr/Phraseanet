<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VideoBurstMode extends AbstractTag
{

    protected $Id = 187;

    protected $Name = 'VideoBurstMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Video Burst Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Post Focus',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '4K Burst',
        ),
        40 => array(
            'Id' => 40,
            'Label' => '4K Burst (Start/Stop)',
        ),
        72 => array(
            'Id' => 72,
            'Label' => '4K Pre-burst',
        ),
        264 => array(
            'Id' => 264,
            'Label' => 'Loop Recording',
        ),
    );

}
