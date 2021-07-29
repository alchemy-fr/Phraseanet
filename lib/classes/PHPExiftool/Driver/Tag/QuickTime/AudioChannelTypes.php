<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioChannelTypes extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'AudioChannelTypes';

    protected $FullName = 'QuickTime::ChannelLayout';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Audio Channel Types';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Right',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Center',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'LFEScreen',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'LeftSurround',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'RightSurround',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'LeftCenter',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'RightCenter',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'CenterSurround',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'LeftSurroundDirect',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'RightSurroundDirect',
        ),
        2048 => array(
            'Id' => 2048,
            'Label' => 'TopCenterSurround',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'VerticalHeightLeft',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'VerticalHeightCenter',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'VerticalHeightRight',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'TopBackLeft',
        ),
        65536 => array(
            'Id' => 65536,
            'Label' => 'TopBackCenter',
        ),
        131072 => array(
            'Id' => 131072,
            'Label' => 'TopBackRight',
        ),
    );

}
