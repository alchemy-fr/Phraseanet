<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AEBracketingSteps extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AEBracketingSteps';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'AE Bracketing Steps';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AE Bracketing Disabled',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'AE Bracketing Disabled',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'AE Bracketing Disabled',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'AE Bracketing Disabled',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'AE Bracketing Disabled',
        ),
        129 => array(
            'Id' => 129,
            'Label' => '+3F0.3',
        ),
        130 => array(
            'Id' => 130,
            'Label' => '-3F0.3',
        ),
        131 => array(
            'Id' => 131,
            'Label' => '+2F0.3',
        ),
        132 => array(
            'Id' => 132,
            'Label' => '-2F0.3',
        ),
        133 => array(
            'Id' => 133,
            'Label' => '3F0.3',
        ),
        134 => array(
            'Id' => 134,
            'Label' => '5F0.3',
        ),
        135 => array(
            'Id' => 135,
            'Label' => '7F0.3',
        ),
        136 => array(
            'Id' => 136,
            'Label' => '9F0.3',
        ),
        145 => array(
            'Id' => 145,
            'Label' => '+3F0.5',
        ),
        146 => array(
            'Id' => 146,
            'Label' => '-3F0.5',
        ),
        147 => array(
            'Id' => 147,
            'Label' => '+2F0.5',
        ),
        148 => array(
            'Id' => 148,
            'Label' => '-2F0.5',
        ),
        149 => array(
            'Id' => 149,
            'Label' => '3F0.5',
        ),
        150 => array(
            'Id' => 150,
            'Label' => '5F0.5',
        ),
        151 => array(
            'Id' => 151,
            'Label' => '7F0.5',
        ),
        152 => array(
            'Id' => 152,
            'Label' => '9F0.5',
        ),
        161 => array(
            'Id' => 161,
            'Label' => '+3F0.7',
        ),
        162 => array(
            'Id' => 162,
            'Label' => '-3F0.7',
        ),
        163 => array(
            'Id' => 163,
            'Label' => '+2F0.7',
        ),
        164 => array(
            'Id' => 164,
            'Label' => '-2F0.7',
        ),
        165 => array(
            'Id' => 165,
            'Label' => '3F0.7',
        ),
        166 => array(
            'Id' => 166,
            'Label' => '5F0.7',
        ),
        167 => array(
            'Id' => 167,
            'Label' => '7F0.7',
        ),
        168 => array(
            'Id' => 168,
            'Label' => '9F0.7',
        ),
        177 => array(
            'Id' => 177,
            'Label' => '+3F1',
        ),
        178 => array(
            'Id' => 178,
            'Label' => '-3F1',
        ),
        179 => array(
            'Id' => 179,
            'Label' => '+2F1',
        ),
        180 => array(
            'Id' => 180,
            'Label' => '-2F1',
        ),
        181 => array(
            'Id' => 181,
            'Label' => '3F1',
        ),
        182 => array(
            'Id' => 182,
            'Label' => '5F1',
        ),
        183 => array(
            'Id' => 183,
            'Label' => '7F1',
        ),
        184 => array(
            'Id' => 184,
            'Label' => '9F1',
        ),
        193 => array(
            'Id' => 193,
            'Label' => '+3F2',
        ),
        194 => array(
            'Id' => 194,
            'Label' => '-3F2',
        ),
        195 => array(
            'Id' => 195,
            'Label' => '+2F2',
        ),
        196 => array(
            'Id' => 196,
            'Label' => '-2F2',
        ),
        197 => array(
            'Id' => 197,
            'Label' => '3F2',
        ),
        198 => array(
            'Id' => 198,
            'Label' => '5F2',
        ),
        209 => array(
            'Id' => 209,
            'Label' => '+3F3',
        ),
        210 => array(
            'Id' => 210,
            'Label' => '-3F3',
        ),
        211 => array(
            'Id' => 211,
            'Label' => '+2F3',
        ),
        212 => array(
            'Id' => 212,
            'Label' => '-2F3',
        ),
        213 => array(
            'Id' => 213,
            'Label' => '3F3',
        ),
        214 => array(
            'Id' => 214,
            'Label' => '5F3',
        ),
    );

}
