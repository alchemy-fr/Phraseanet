<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\JFXX;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ThumbnailTIFF extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ThumbnailTIFF';

    protected $FullName = 'JFIF::Extension';

    protected $GroupName = 'JFXX';

    protected $g0 = 'JFIF';

    protected $g1 = 'JFXX';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Thumbnail TIFF';

    protected $local_g2 = 'Preview';

}
