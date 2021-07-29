<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiFilm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HighlightTone extends AbstractTag
{

    protected $Id = 4161;

    protected $Name = 'HighlightTone';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Highlight Tone';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-64' => array(
            'Id' => '-64',
            'Label' => '+4 (hardest)',
        ),
        '-48' => array(
            'Id' => '-48',
            'Label' => '+3 (very hard)',
        ),
        '-32' => array(
            'Id' => '-32',
            'Label' => '+2 (hard)',
        ),
        '-16' => array(
            'Id' => '-16',
            'Label' => '+1 (medium hard)',
        ),
        0 => array(
            'Id' => 0,
            'Label' => '0 (normal)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '-1 (medium soft)',
        ),
        32 => array(
            'Id' => 32,
            'Label' => '-2 (soft)',
        ),
    );

}
