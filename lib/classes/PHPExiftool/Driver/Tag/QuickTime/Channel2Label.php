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
class Channel2Label extends AbstractTag
{

    protected $Id = 36;

    protected $Name = 'Channel2Label';

    protected $FullName = 'QuickTime::ChannelLayout';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Channel 2 Label';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unused',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Right',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Center',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'LFEScreen',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'LeftSurround',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'RightSurround',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'LeftCenter',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'RightCenter',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'CenterSurround',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'LeftSurroundDirect',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'RightSurroundDirect',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'TopCenterSurround',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'VerticalHeightLeft',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'VerticalHeightCenter',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'VerticalHeightRight',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'TopBackLeft',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'TopBackCenter',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'TopBackRight',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'RearSurroundLeft',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'RearSurroundRight',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'LeftWide',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'RightWide',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'LFE2',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'LeftTotal',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'RightTotal',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'HearingImpaired',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Narration',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'Mono',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'DialogCentricMix',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'CenterSurroundDirect',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Haptic',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'UseCoordinates',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Ambisonic_W',
        ),
        201 => array(
            'Id' => 201,
            'Label' => 'Ambisonic_X',
        ),
        202 => array(
            'Id' => 202,
            'Label' => 'Ambisonic_Y',
        ),
        203 => array(
            'Id' => 203,
            'Label' => 'Ambisonic_Z',
        ),
        204 => array(
            'Id' => 204,
            'Label' => 'MS_Mid',
        ),
        205 => array(
            'Id' => 205,
            'Label' => 'MS_Side',
        ),
        206 => array(
            'Id' => 206,
            'Label' => 'XY_X',
        ),
        207 => array(
            'Id' => 207,
            'Label' => 'XY_Y',
        ),
        301 => array(
            'Id' => 301,
            'Label' => 'HeadphonesLeft',
        ),
        302 => array(
            'Id' => 302,
            'Label' => 'HeadphonesRight',
        ),
        304 => array(
            'Id' => 304,
            'Label' => 'ClickTrack',
        ),
        305 => array(
            'Id' => 305,
            'Label' => 'ForeignLanguage',
        ),
        400 => array(
            'Id' => 400,
            'Label' => 'Discrete',
        ),
        65536 => array(
            'Id' => 65536,
            'Label' => 'Discrete_0',
        ),
        65537 => array(
            'Id' => 65537,
            'Label' => 'Discrete_1',
        ),
        65538 => array(
            'Id' => 65538,
            'Label' => 'Discrete_2',
        ),
        65539 => array(
            'Id' => 65539,
            'Label' => 'Discrete_3',
        ),
        65540 => array(
            'Id' => 65540,
            'Label' => 'Discrete_4',
        ),
        65541 => array(
            'Id' => 65541,
            'Label' => 'Discrete_5',
        ),
        65542 => array(
            'Id' => 65542,
            'Label' => 'Discrete_6',
        ),
        65543 => array(
            'Id' => 65543,
            'Label' => 'Discrete_7',
        ),
        65544 => array(
            'Id' => 65544,
            'Label' => 'Discrete_8',
        ),
        65545 => array(
            'Id' => 65545,
            'Label' => 'Discrete_9',
        ),
        65546 => array(
            'Id' => 65546,
            'Label' => 'Discrete_10',
        ),
        65547 => array(
            'Id' => 65547,
            'Label' => 'Discrete_11',
        ),
        65548 => array(
            'Id' => 65548,
            'Label' => 'Discrete_12',
        ),
        65549 => array(
            'Id' => 65549,
            'Label' => 'Discrete_13',
        ),
        65550 => array(
            'Id' => 65550,
            'Label' => 'Discrete_14',
        ),
        65551 => array(
            'Id' => 65551,
            'Label' => 'Discrete_15',
        ),
        131071 => array(
            'Id' => 131071,
            'Label' => 'Discrete_65535',
        ),
        '4294967295' => array(
            'Id' => '4294967295',
            'Label' => 'Unknown',
        ),
    );

}
