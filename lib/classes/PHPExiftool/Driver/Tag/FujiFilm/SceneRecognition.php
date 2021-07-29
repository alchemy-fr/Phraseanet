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
class SceneRecognition extends AbstractTag
{

    protected $Id = 5157;

    protected $Name = 'SceneRecognition';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Scene Recognition';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unrecognized',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Portrait Image',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'Night Portrait',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'Backlit Portrait',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Landscape Image',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Night Scene',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Macro',
        ),
    );

}
