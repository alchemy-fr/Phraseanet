<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\File;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Encoding extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Encoding';

    protected $FullName = 'mixed';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Encoding';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'FLIF16',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'RLE',
        ),
    );

}
