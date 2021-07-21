<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Matroska;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class DisplayUnit extends AbstractTag
{

    protected $Id = 5298;

    protected $Name = 'DisplayUnit';

    protected $FullName = 'Matroska::Main';

    protected $GroupName = 'Matroska';

    protected $g0 = 'Matroska';

    protected $g1 = 'Matroska';

    protected $g2 = 'Video';

    protected $Type = 'unsigned';

    protected $Writable = false;

    protected $Description = 'Display Unit';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Pixels',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'cm',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'inches',
        ),
    );

}
