<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\GoPro;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AutoRotation extends AbstractTag
{

    protected $Id = 'OREN';

    protected $Name = 'AutoRotation';

    protected $FullName = 'GoPro::GPMF';

    protected $GroupName = 'GoPro';

    protected $g0 = 'GoPro';

    protected $g1 = 'GoPro';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Auto Rotation';

    protected $Values = array(
        'A' => array(
            'Id' => 'A',
            'Label' => 'Auto',
        ),
        'D' => array(
            'Id' => 'D',
            'Label' => 'Down',
        ),
        'U' => array(
            'Id' => 'U',
            'Label' => 'Up',
        ),
    );

}
