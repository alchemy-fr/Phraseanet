<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPExifEX;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Acceleration extends AbstractTag
{

    protected $Id = 'Acceleration';

    protected $Name = 'Acceleration';

    protected $FullName = 'XMP::exifEX';

    protected $GroupName = 'XMP-exifEX';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exifEX';

    protected $g2 = 'Image';

    protected $Type = 'rational';

    protected $Writable = true;

    protected $Description = 'Acceleration';

}
