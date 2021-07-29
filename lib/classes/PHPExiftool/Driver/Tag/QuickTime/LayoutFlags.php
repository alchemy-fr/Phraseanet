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
class LayoutFlags extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'LayoutFlags';

    protected $FullName = 'QuickTime::ChannelLayout';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Layout Flags';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'UseDescriptions',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'UseBitmap',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'Mono',
        ),
        101 => array(
            'Id' => 101,
            'Label' => 'Stereo',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'StereoHeadphones',
        ),
        103 => array(
            'Id' => 103,
            'Label' => 'MatrixStereo',
        ),
        104 => array(
            'Id' => 104,
            'Label' => 'MidSide',
        ),
        105 => array(
            'Id' => 105,
            'Label' => 'XY',
        ),
        106 => array(
            'Id' => 106,
            'Label' => 'Binaural',
        ),
        107 => array(
            'Id' => 107,
            'Label' => 'Ambisonic_B_Format',
        ),
        108 => array(
            'Id' => 108,
            'Label' => 'Quadraphonic',
        ),
        109 => array(
            'Id' => 109,
            'Label' => 'Pentagonal',
        ),
        110 => array(
            'Id' => 110,
            'Label' => 'Hexagonal',
        ),
        111 => array(
            'Id' => 111,
            'Label' => 'Octagonal',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'Cube',
        ),
        113 => array(
            'Id' => 113,
            'Label' => 'MPEG_3_0_A',
        ),
        114 => array(
            'Id' => 114,
            'Label' => 'MPEG_3_0_B',
        ),
        115 => array(
            'Id' => 115,
            'Label' => 'MPEG_4_0_A',
        ),
        116 => array(
            'Id' => 116,
            'Label' => 'MPEG_4_0_B',
        ),
        117 => array(
            'Id' => 117,
            'Label' => 'MPEG_5_0_A',
        ),
        118 => array(
            'Id' => 118,
            'Label' => 'MPEG_5_0_B',
        ),
        119 => array(
            'Id' => 119,
            'Label' => 'MPEG_5_0_C',
        ),
        120 => array(
            'Id' => 120,
            'Label' => 'MPEG_5_0_D',
        ),
        121 => array(
            'Id' => 121,
            'Label' => 'MPEG_5_1_A',
        ),
        122 => array(
            'Id' => 122,
            'Label' => 'MPEG_5_1_B',
        ),
        123 => array(
            'Id' => 123,
            'Label' => 'MPEG_5_1_C',
        ),
        124 => array(
            'Id' => 124,
            'Label' => 'MPEG_5_1_D',
        ),
        125 => array(
            'Id' => 125,
            'Label' => 'MPEG_6_1_A',
        ),
        126 => array(
            'Id' => 126,
            'Label' => 'MPEG_7_1_A',
        ),
        127 => array(
            'Id' => 127,
            'Label' => 'MPEG_7_1_B',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'MPEG_7_1_C',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Emagic_Default_7_1',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'SMPTE_DTV',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'ITU_2_1',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'ITU_2_2',
        ),
        133 => array(
            'Id' => 133,
            'Label' => 'DVD_4',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'DVD_5',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'DVD_6',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'DVD_10',
        ),
        137 => array(
            'Id' => 137,
            'Label' => 'DVD_11',
        ),
        138 => array(
            'Id' => 138,
            'Label' => 'DVD_18',
        ),
        139 => array(
            'Id' => 139,
            'Label' => 'AudioUnit_6_0',
        ),
        140 => array(
            'Id' => 140,
            'Label' => 'AudioUnit_7_0',
        ),
        141 => array(
            'Id' => 141,
            'Label' => 'AAC_6_0',
        ),
        142 => array(
            'Id' => 142,
            'Label' => 'AAC_6_1',
        ),
        143 => array(
            'Id' => 143,
            'Label' => 'AAC_7_0',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'AAC_Octagonal',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'TMH_10_2_std',
        ),
        146 => array(
            'Id' => 146,
            'Label' => 'TMH_10_2_full',
        ),
        147 => array(
            'Id' => 147,
            'Label' => 'DiscreteInOrder',
        ),
        148 => array(
            'Id' => 148,
            'Label' => 'AudioUnit_7_0_Front',
        ),
        149 => array(
            'Id' => 149,
            'Label' => 'AC3_1_0_1',
        ),
        150 => array(
            'Id' => 150,
            'Label' => 'AC3_3_0',
        ),
        151 => array(
            'Id' => 151,
            'Label' => 'AC3_3_1',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'AC3_3_0_1',
        ),
        153 => array(
            'Id' => 153,
            'Label' => 'AC3_2_1_1',
        ),
        154 => array(
            'Id' => 154,
            'Label' => 'AC3_3_1_1',
        ),
        155 => array(
            'Id' => 155,
            'Label' => 'EAC_6_0_A',
        ),
        156 => array(
            'Id' => 156,
            'Label' => 'EAC_7_0_A',
        ),
        157 => array(
            'Id' => 157,
            'Label' => 'EAC3_6_1_A',
        ),
        158 => array(
            'Id' => 158,
            'Label' => 'EAC3_6_1_B',
        ),
        159 => array(
            'Id' => 159,
            'Label' => 'EAC3_6_1_C',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'EAC3_7_1_A',
        ),
        161 => array(
            'Id' => 161,
            'Label' => 'EAC3_7_1_B',
        ),
        162 => array(
            'Id' => 162,
            'Label' => 'EAC3_7_1_C',
        ),
        163 => array(
            'Id' => 163,
            'Label' => 'EAC3_7_1_D',
        ),
        164 => array(
            'Id' => 164,
            'Label' => 'EAC3_7_1_E',
        ),
        165 => array(
            'Id' => 165,
            'Label' => 'EAC3_7_1_F',
        ),
        166 => array(
            'Id' => 166,
            'Label' => 'EAC3_7_1_G',
        ),
        167 => array(
            'Id' => 167,
            'Label' => 'EAC3_7_1_H',
        ),
        168 => array(
            'Id' => 168,
            'Label' => 'DTS_3_1',
        ),
        169 => array(
            'Id' => 169,
            'Label' => 'DTS_4_1',
        ),
        170 => array(
            'Id' => 170,
            'Label' => 'DTS_6_0_A',
        ),
        171 => array(
            'Id' => 171,
            'Label' => 'DTS_6_0_B',
        ),
        172 => array(
            'Id' => 172,
            'Label' => 'DTS_6_0_C',
        ),
        173 => array(
            'Id' => 173,
            'Label' => 'DTS_6_1_A',
        ),
        174 => array(
            'Id' => 174,
            'Label' => 'DTS_6_1_B',
        ),
        175 => array(
            'Id' => 175,
            'Label' => 'DTS_6_1_C',
        ),
        176 => array(
            'Id' => 176,
            'Label' => 'DTS_7_0',
        ),
        177 => array(
            'Id' => 177,
            'Label' => 'DTS_7_1',
        ),
        178 => array(
            'Id' => 178,
            'Label' => 'DTS_8_0_A',
        ),
        179 => array(
            'Id' => 179,
            'Label' => 'DTS_8_0_B',
        ),
        180 => array(
            'Id' => 180,
            'Label' => 'DTS_8_1_A',
        ),
        181 => array(
            'Id' => 181,
            'Label' => 'DTS_8_1_B',
        ),
        182 => array(
            'Id' => 182,
            'Label' => 'DTS_6_1_D',
        ),
        183 => array(
            'Id' => 183,
            'Label' => 'AAC_7_1_B',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'Unknown',
        ),
    );

}
