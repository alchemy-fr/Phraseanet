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
class AFAreaMode extends AbstractTag
{

    protected $Id = '0.2';

    protected $Name = 'AFAreaMode';

    protected $FullName = 'FujiFilm::FocusSettings';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'AF Area Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single Point',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Zone',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Wide/Tracking',
        ),
    );

}
