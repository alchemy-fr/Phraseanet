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
class FocusMode2 extends AbstractTag
{

    protected $Id = '0.1';

    protected $Name = 'FocusMode2';

    protected $FullName = 'FujiFilm::FocusSettings';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Focus Mode 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF-M',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AF-S',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF-C',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'AF-S (Auto)',
        ),
    );

}
