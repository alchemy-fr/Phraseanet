<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MIEThumbnail;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ThumbnailImage extends AbstractTag
{

    protected $Id = 'data';

    protected $Name = 'ThumbnailImage';

    protected $FullName = 'MIE::Thumbnail';

    protected $GroupName = 'MIE-Thumbnail';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Thumbnail';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Thumbnail Image';

    protected $local_g2 = 'Preview';

    protected $flag_Binary = true;

}
