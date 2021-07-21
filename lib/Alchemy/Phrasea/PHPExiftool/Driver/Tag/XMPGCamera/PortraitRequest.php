<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPGCamera;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PortraitRequest extends AbstractTag
{

    protected $Id = 'PortraitRequest';

    protected $Name = 'PortraitRequest';

    protected $FullName = 'XMP::GCamera';

    protected $GroupName = 'XMP-GCamera';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GCamera';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Portrait Request';

}
