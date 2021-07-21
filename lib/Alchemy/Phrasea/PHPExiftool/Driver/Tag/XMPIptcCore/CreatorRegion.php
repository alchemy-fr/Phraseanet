<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPIptcCore;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CreatorRegion extends AbstractTag
{

    protected $Id = 'CreatorContactInfoCiAdrRegion';

    protected $Name = 'CreatorRegion';

    protected $FullName = 'XMP::iptcCore';

    protected $GroupName = 'XMP-iptcCore';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-iptcCore';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Creator Region';

}
