<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPDigiKam;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ImageHistory extends AbstractTag
{

    protected $Id = 'ImageHistory';

    protected $Name = 'ImageHistory';

    protected $FullName = 'XMP::digiKam';

    protected $GroupName = 'XMP-digiKam';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-digiKam';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image History';

    protected $flag_Avoid = true;

}
