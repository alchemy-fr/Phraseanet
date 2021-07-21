<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPGImage;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ImageData extends AbstractTag
{

    protected $Id = 'Data';

    protected $Name = 'ImageData';

    protected $FullName = 'XMP::GImage';

    protected $GroupName = 'XMP-GImage';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GImage';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Data';

}
