<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPDc;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Format extends AbstractTag
{

    protected $Id = 'format';

    protected $Name = 'Format';

    protected $FullName = 'XMP::dc';

    protected $GroupName = 'XMP-dc';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-dc';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Format';

    protected $local_g2 = 'Image';

}
