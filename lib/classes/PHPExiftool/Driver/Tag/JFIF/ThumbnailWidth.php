<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\JFIF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ThumbnailWidth extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'ThumbnailWidth';

    protected $FullName = 'JFIF::Main';

    protected $GroupName = 'JFIF';

    protected $g0 = 'JFIF';

    protected $g1 = 'JFIF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Thumbnail Width';

}
