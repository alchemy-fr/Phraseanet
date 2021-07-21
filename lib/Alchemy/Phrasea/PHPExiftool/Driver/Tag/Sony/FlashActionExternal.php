<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Sony;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class FlashActionExternal extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashActionExternal';

    protected $FullName = 'Sony::MoreSettings';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Action External';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 121,
            'Label' => 'Fired',
        ),
        1 => array(
            'Id' => 122,
            'Label' => 'Fired',
        ),
        2 => array(
            'Id' => 136,
            'Label' => 'Did not fire',
        ),
        3 => array(
            'Id' => 136,
            'Label' => 'Did not fire',
        ),
        4 => array(
            'Id' => 167,
            'Label' => 'Fired',
        ),
        5 => array(
            'Id' => 182,
            'Label' => 'Fired, HSS',
        ),
    );

}
