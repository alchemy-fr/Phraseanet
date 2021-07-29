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
class AFCSetting extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'AF-CSetting';

    protected $FullName = 'FujiFilm::AFCSettings';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'AF-C Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Set 4 (suddenly appearing subject)',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'Set 1 (multi-purpose)',
        ),
        290 => array(
            'Id' => 290,
            'Label' => 'Set 3 (accelerating subject)',
        ),
        291 => array(
            'Id' => 291,
            'Label' => 'Set 5 (erratic motion)',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Set 2 (ignore obstacles)',
        ),
    );

}
