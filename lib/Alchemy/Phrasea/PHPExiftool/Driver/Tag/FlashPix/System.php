<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\FlashPix;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class System extends AbstractTag
{

    protected $Id = '9.1';

    protected $Name = 'System';

    protected $FullName = 'FlashPix::WordDocument';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'System';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Windows',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Macintosh',
        ),
    );

}
