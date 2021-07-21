<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\JPEGHDR;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class S2n extends AbstractTag
{

    protected $Id = 's2n';

    protected $Name = 'S2n';

    protected $FullName = 'JPEG::HDR';

    protected $GroupName = 'JPEG-HDR';

    protected $g0 = 'APP11';

    protected $g1 = 'JPEG-HDR';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'S2n';

}
