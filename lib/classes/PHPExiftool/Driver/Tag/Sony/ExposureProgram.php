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
class ExposureProgram extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureProgram';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Exposure Program';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        13 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        14 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        15 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        16 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        17 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        18 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        19 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        20 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        21 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        22 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        23 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        24 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        25 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        26 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        27 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        28 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        29 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        30 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        31 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        32 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        33 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        34 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        35 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        36 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        37 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        38 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        39 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        40 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        41 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        42 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        43 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        44 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        45 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        46 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        47 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        48 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        49 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        50 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        51 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        52 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        53 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        54 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        55 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        56 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        57 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        58 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        59 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        60 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        61 => array(
            'Id' => 1,
            'Label' => 'Program AE',
        ),
        62 => array(
            'Id' => 2,
            'Label' => 'Aperture-priority AE',
        ),
        63 => array(
            'Id' => 3,
            'Label' => 'Shutter speed priority AE',
        ),
        64 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        65 => array(
            'Id' => 5,
            'Label' => 'Cont. Priority AE',
        ),
        66 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        67 => array(
            'Id' => 17,
            'Label' => 'Auto (no flash)',
        ),
        68 => array(
            'Id' => 18,
            'Label' => 'Auto+',
        ),
        69 => array(
            'Id' => 49,
            'Label' => 'Portrait',
        ),
        70 => array(
            'Id' => 50,
            'Label' => 'Landscape',
        ),
        71 => array(
            'Id' => 51,
            'Label' => 'Macro',
        ),
        72 => array(
            'Id' => 52,
            'Label' => 'Sports',
        ),
        73 => array(
            'Id' => 53,
            'Label' => 'Sunset',
        ),
        74 => array(
            'Id' => 54,
            'Label' => 'Night view',
        ),
        75 => array(
            'Id' => 55,
            'Label' => 'Night view/portrait',
        ),
        76 => array(
            'Id' => 56,
            'Label' => 'Handheld Night Shot',
        ),
        77 => array(
            'Id' => 57,
            'Label' => '3D Sweep Panorama',
        ),
        78 => array(
            'Id' => 64,
            'Label' => 'Auto 2',
        ),
        79 => array(
            'Id' => 65,
            'Label' => 'Auto 2 (no flash)',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Sweep Panorama',
        ),
        81 => array(
            'Id' => 96,
            'Label' => 'Anti Motion Blur',
        ),
        82 => array(
            'Id' => 128,
            'Label' => 'Toy Camera',
        ),
        83 => array(
            'Id' => 129,
            'Label' => 'Pop Color',
        ),
        84 => array(
            'Id' => 130,
            'Label' => 'Posterization',
        ),
        85 => array(
            'Id' => 131,
            'Label' => 'Posterization B/W',
        ),
        86 => array(
            'Id' => 132,
            'Label' => 'Retro Photo',
        ),
        87 => array(
            'Id' => 133,
            'Label' => 'High-key',
        ),
        88 => array(
            'Id' => 134,
            'Label' => 'Partial Color Red',
        ),
        89 => array(
            'Id' => 135,
            'Label' => 'Partial Color Green',
        ),
        90 => array(
            'Id' => 136,
            'Label' => 'Partial Color Blue',
        ),
        91 => array(
            'Id' => 137,
            'Label' => 'Partial Color Yellow',
        ),
        92 => array(
            'Id' => 138,
            'Label' => 'High Contrast Monochrome',
        ),
        93 => array(
            'Id' => 241,
            'Label' => 'Landscape',
        ),
        94 => array(
            'Id' => 243,
            'Label' => 'Aperture-priority AE',
        ),
        95 => array(
            'Id' => 245,
            'Label' => 'Portrait',
        ),
        96 => array(
            'Id' => 246,
            'Label' => 'Auto',
        ),
        97 => array(
            'Id' => 247,
            'Label' => 'Program AE',
        ),
        98 => array(
            'Id' => 249,
            'Label' => 'Macro',
        ),
        99 => array(
            'Id' => 252,
            'Label' => 'Sunset',
        ),
        100 => array(
            'Id' => 253,
            'Label' => 'Sports',
        ),
        101 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
        102 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        103 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        104 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        105 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        106 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        107 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        108 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        109 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        110 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        111 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        112 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        113 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        114 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        115 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        116 => array(
            'Id' => 1,
            'Label' => 'Program AE',
        ),
        117 => array(
            'Id' => 2,
            'Label' => 'Aperture-priority AE',
        ),
        118 => array(
            'Id' => 3,
            'Label' => 'Shutter speed priority AE',
        ),
        119 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        120 => array(
            'Id' => 5,
            'Label' => 'Cont. Priority AE',
        ),
        121 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        122 => array(
            'Id' => 17,
            'Label' => 'Auto (no flash)',
        ),
        123 => array(
            'Id' => 18,
            'Label' => 'Auto+',
        ),
        124 => array(
            'Id' => 49,
            'Label' => 'Portrait',
        ),
        125 => array(
            'Id' => 50,
            'Label' => 'Landscape',
        ),
        126 => array(
            'Id' => 51,
            'Label' => 'Macro',
        ),
        127 => array(
            'Id' => 52,
            'Label' => 'Sports',
        ),
        128 => array(
            'Id' => 53,
            'Label' => 'Sunset',
        ),
        129 => array(
            'Id' => 54,
            'Label' => 'Night view',
        ),
        130 => array(
            'Id' => 55,
            'Label' => 'Night view/portrait',
        ),
        131 => array(
            'Id' => 56,
            'Label' => 'Handheld Night Shot',
        ),
        132 => array(
            'Id' => 57,
            'Label' => '3D Sweep Panorama',
        ),
        133 => array(
            'Id' => 64,
            'Label' => 'Auto 2',
        ),
        134 => array(
            'Id' => 65,
            'Label' => 'Auto 2 (no flash)',
        ),
        135 => array(
            'Id' => 80,
            'Label' => 'Sweep Panorama',
        ),
        136 => array(
            'Id' => 96,
            'Label' => 'Anti Motion Blur',
        ),
        137 => array(
            'Id' => 128,
            'Label' => 'Toy Camera',
        ),
        138 => array(
            'Id' => 129,
            'Label' => 'Pop Color',
        ),
        139 => array(
            'Id' => 130,
            'Label' => 'Posterization',
        ),
        140 => array(
            'Id' => 131,
            'Label' => 'Posterization B/W',
        ),
        141 => array(
            'Id' => 132,
            'Label' => 'Retro Photo',
        ),
        142 => array(
            'Id' => 133,
            'Label' => 'High-key',
        ),
        143 => array(
            'Id' => 134,
            'Label' => 'Partial Color Red',
        ),
        144 => array(
            'Id' => 135,
            'Label' => 'Partial Color Green',
        ),
        145 => array(
            'Id' => 136,
            'Label' => 'Partial Color Blue',
        ),
        146 => array(
            'Id' => 137,
            'Label' => 'Partial Color Yellow',
        ),
        147 => array(
            'Id' => 138,
            'Label' => 'High Contrast Monochrome',
        ),
        148 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        149 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        150 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        151 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        152 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        153 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        154 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        155 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        156 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        157 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        158 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        159 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        160 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        161 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        162 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        163 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        164 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        165 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        166 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        167 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        168 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        169 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        170 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        171 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        172 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        173 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        174 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        175 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        176 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        177 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        178 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        179 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        180 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        181 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        182 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        183 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        184 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        185 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        186 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        187 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        188 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        189 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        190 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        191 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        192 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        193 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        194 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        195 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        196 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        197 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        198 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        199 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        200 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        201 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        202 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        203 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        204 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        205 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        206 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        207 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        208 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        209 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        210 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        211 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        212 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        213 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        214 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        215 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        216 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        217 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        218 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        219 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        220 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        221 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        222 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        223 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        224 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        225 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        226 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        227 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        228 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        229 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        230 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        231 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        232 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        233 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        234 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        235 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        236 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        237 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        238 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        239 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        240 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        241 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        242 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        243 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        244 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        245 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        246 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        247 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        248 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        249 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        250 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        251 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        252 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        253 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        254 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        255 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        256 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        257 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        258 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        259 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        260 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        261 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        262 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        263 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        264 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        265 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        266 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        267 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        268 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        269 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        270 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        271 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        272 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        273 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        274 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        275 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        276 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        277 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        278 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        279 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        280 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        281 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        282 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        283 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        284 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        285 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        286 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        287 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        288 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        289 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        290 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        291 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        292 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        293 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        294 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        295 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        296 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        297 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        298 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        299 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        300 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        301 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        302 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        303 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        304 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        305 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        306 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        307 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        308 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        309 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        310 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        311 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        312 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        313 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        314 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        315 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        316 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        317 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        318 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        319 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        320 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        321 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        322 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        323 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        324 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        325 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        326 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        327 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        328 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        329 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        330 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        331 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        332 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        333 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        334 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        335 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        336 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        337 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        338 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        339 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        340 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        341 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        342 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        343 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        344 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        345 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        346 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        347 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        348 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        349 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        350 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        351 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        352 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        353 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        354 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        355 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        356 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        357 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        358 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        359 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        360 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        361 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        362 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        363 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        364 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        365 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        366 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        367 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        368 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        369 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        370 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        371 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        372 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        373 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        374 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        375 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        376 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        377 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        378 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        379 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        380 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        381 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        382 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        383 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        384 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        385 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        386 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        387 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        388 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        389 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        390 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        391 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        392 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        393 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        394 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        395 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        396 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        397 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        398 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        399 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        400 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        401 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        402 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        403 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        404 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        405 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        406 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        407 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        408 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        409 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        410 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        411 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        412 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        413 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        414 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        415 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        416 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        417 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        418 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        419 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        420 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        421 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        422 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        423 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        424 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        425 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        426 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        427 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        428 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        429 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        430 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        431 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        432 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        433 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        434 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        435 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        436 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        437 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        438 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        439 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        440 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        441 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        442 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        443 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        444 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        445 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        446 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        447 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        448 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        449 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        450 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        451 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        452 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        453 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        454 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        455 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        456 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        457 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        458 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        459 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        460 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        461 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        462 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        463 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        464 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        465 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        466 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        467 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        468 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        469 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        470 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        471 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        472 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        473 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        474 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        475 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        476 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        477 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        478 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        479 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        480 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        481 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        482 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        483 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        484 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        485 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        486 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        487 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        488 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        489 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        490 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        491 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        492 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        493 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        494 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        495 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        496 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        497 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        498 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        499 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        500 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        501 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        502 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        503 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        504 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        505 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        506 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        507 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        508 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        509 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        510 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        511 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        512 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        513 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        514 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        515 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        516 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        517 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        518 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        519 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        520 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        521 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        522 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        523 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        524 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        525 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        526 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        527 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        528 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        529 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        530 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        531 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        532 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        533 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        534 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        535 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        536 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        537 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        538 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        539 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        540 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        541 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        542 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        543 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
        544 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        545 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        546 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        547 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        548 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        549 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        550 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        551 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        552 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        553 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        554 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        555 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        556 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        557 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        558 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        559 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        560 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        561 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        562 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        563 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        564 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        565 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        566 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        567 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        568 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        569 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        570 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        571 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        572 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        573 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        574 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        575 => array(
            'Id' => 45,
            'Label' => 'Document',
        ),
        576 => array(
            'Id' => 46,
            'Label' => 'Party',
        ),
    );

    protected $Index = 'mixed';

}
