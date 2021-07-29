<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PreviewButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Preview Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Flash Off',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Bracketing Burst',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Matrix Metering',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Center-weighted Metering',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Spot Metering',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Virtual Horizon',
        ),
        14 => array(
            'Id' => 15,
            'Label' => 'Playback',
        ),
        15 => array(
            'Id' => 16,
            'Label' => 'My Menu Top',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        18 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        19 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        20 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        21 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        22 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        23 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        24 => array(
            'Id' => 9,
            'Label' => 'Flash Off',
        ),
        25 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        26 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        27 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        28 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        29 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        30 => array(
            'Id' => 15,
            'Label' => 'My Menu Top',
        ),
        31 => array(
            'Id' => 16,
            'Label' => '+ NEF (RAW)',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        33 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        34 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        35 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        36 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        37 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        38 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        39 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        40 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        41 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        42 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        43 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        44 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        45 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        46 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        47 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        48 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        49 => array(
            'Id' => 18,
            'Label' => 'My Menu',
        ),
        50 => array(
            'Id' => 20,
            'Label' => 'Grid Display',
        ),
        51 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        52 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        53 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        54 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        55 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        56 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        57 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        58 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        59 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        60 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        61 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        62 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        63 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        64 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        65 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        66 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        67 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        68 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        69 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        70 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        71 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        72 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        73 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        74 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        75 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        76 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        77 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        78 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        79 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        80 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        81 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        82 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        83 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        84 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        85 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        86 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        87 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        88 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        89 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        90 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        91 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        92 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        93 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        94 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        95 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        96 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        97 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        98 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        99 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        100 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        101 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        102 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        103 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        104 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        105 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        106 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        107 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        108 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        109 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        110 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        111 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        112 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        113 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        114 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        115 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        116 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        117 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        118 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        119 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        120 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        121 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        122 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        123 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        124 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        125 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        126 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        127 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        128 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        129 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        130 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        131 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        132 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        133 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        134 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        135 => array(
            'Id' => 8,
            'Label' => 'AF-ON',
        ),
        136 => array(
            'Id' => 9,
            'Label' => 'Flash Off',
        ),
        137 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        138 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        139 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        140 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        141 => array(
            'Id' => 14,
            'Label' => 'My Menu Top',
        ),
        142 => array(
            'Id' => 15,
            'Label' => 'Live View',
        ),
        143 => array(
            'Id' => 16,
            'Label' => '+ NEF (RAW)',
        ),
        144 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        145 => array(
            'Id' => 0,
            'Label' => 'Grid Display',
        ),
        146 => array(
            'Id' => 1,
            'Label' => 'FV Lock',
        ),
        147 => array(
            'Id' => 2,
            'Label' => 'Flash Off',
        ),
        148 => array(
            'Id' => 3,
            'Label' => 'Matrix Metering',
        ),
        149 => array(
            'Id' => 4,
            'Label' => 'Center-weighted Metering',
        ),
        150 => array(
            'Id' => 5,
            'Label' => 'Spot Metering',
        ),
        151 => array(
            'Id' => 6,
            'Label' => 'My Menu Top',
        ),
        152 => array(
            'Id' => 7,
            'Label' => '+ NEF (RAW)',
        ),
        153 => array(
            'Id' => 8,
            'Label' => 'Active D-Lighting',
        ),
        154 => array(
            'Id' => 9,
            'Label' => 'Preview',
        ),
        155 => array(
            'Id' => 10,
            'Label' => 'AE/AF Lock',
        ),
        156 => array(
            'Id' => 11,
            'Label' => 'AE Lock Only',
        ),
        157 => array(
            'Id' => 12,
            'Label' => 'AF Lock Only',
        ),
        158 => array(
            'Id' => 13,
            'Label' => 'AE Lock (hold)',
        ),
        159 => array(
            'Id' => 14,
            'Label' => 'Bracketing Burst',
        ),
        160 => array(
            'Id' => 15,
            'Label' => 'Playback',
        ),
        161 => array(
            'Id' => 16,
            'Label' => '1EV Step Speed/Aperture',
        ),
        162 => array(
            'Id' => 17,
            'Label' => 'Choose Non-CPU Lens',
        ),
        163 => array(
            'Id' => 18,
            'Label' => 'Virtual Horizon',
        ),
        164 => array(
            'Id' => 19,
            'Label' => 'Start Movie Recording',
        ),
        165 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        166 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        167 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        168 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        169 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        170 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        171 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        172 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        173 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        174 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        175 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        176 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        177 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        178 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        179 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        180 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        181 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        182 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        183 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        184 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        185 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        186 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        187 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        188 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        189 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        190 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        191 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        192 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        193 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        194 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        195 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        196 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        197 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        198 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        199 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        200 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        201 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        202 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        203 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        204 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        205 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        206 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        207 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        208 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        209 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        210 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        211 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        212 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        213 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 153 Points)',
        ),
        214 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        215 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        216 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        217 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        218 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        219 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 153 Points)',
        ),
        220 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        221 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        222 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        223 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        224 => array(
            'Id' => 56,
            'Label' => 'AF-Area Mode (Dynamic Area 9 Points)',
        ),
        225 => array(
            'Id' => 57,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 9 Points)',
        ),
    );

    protected $Index = 'mixed';

}
