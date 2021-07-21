<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\EXE;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CPUByteOrder extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CPUByteOrder';

    protected $FullName = 'mixed';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'CPU Byte Order';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Little endian',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Big endian',
        ),
    );

}
