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
class PanoramaDirection extends AbstractTag
{

    protected $Id = 4436;

    protected $Name = 'PanoramaDirection';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Panorama Direction';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Right',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Up',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Left',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Down',
        ),
    );

}
