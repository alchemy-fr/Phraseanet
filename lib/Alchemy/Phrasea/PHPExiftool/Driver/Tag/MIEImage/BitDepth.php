<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MIEImage;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class BitDepth extends AbstractTag
{

    protected $Id = 'BitDepth';

    protected $Name = 'BitDepth';

    protected $FullName = 'MIE::Image';

    protected $GroupName = 'MIE-Image';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Image';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Bit Depth';

}
