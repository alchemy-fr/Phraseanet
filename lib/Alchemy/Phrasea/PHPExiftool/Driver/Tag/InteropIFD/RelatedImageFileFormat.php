<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\InteropIFD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RelatedImageFileFormat extends AbstractTag
{

    protected $Id = 4096;

    protected $Name = 'RelatedImageFileFormat';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'InteropIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Related Image File Format';

    protected $local_g1 = 'InteropIFD';

    protected $flag_Unsafe = true;

}
