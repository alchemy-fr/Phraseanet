<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Flash;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class FlashAttributes extends AbstractTag
{

    protected $Id = 69;

    protected $Name = 'FlashAttributes';

    protected $FullName = 'Flash::Main';

    protected $GroupName = 'Flash';

    protected $g0 = 'Flash';

    protected $g1 = 'Flash';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Flash Attributes';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'UseNetwork',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'ActionScript3',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'HasMetadata',
        ),
    );

}
