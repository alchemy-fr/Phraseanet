<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPLImage;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MajorVersion extends AbstractTag
{

    protected $Id = 'MajorVersion';

    protected $Name = 'MajorVersion';

    protected $FullName = 'XMP::LImage';

    protected $GroupName = 'XMP-LImage';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-LImage';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Major Version';

}
