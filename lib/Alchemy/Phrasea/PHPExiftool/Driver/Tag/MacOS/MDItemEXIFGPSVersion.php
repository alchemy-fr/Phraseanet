<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MacOS;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class MDItemEXIFGPSVersion extends AbstractTag
{

    protected $Id = 'MDItemEXIFGPSVersion';

    protected $Name = 'MDItemEXIFGPSVersion';

    protected $FullName = 'MacOS::MDItem';

    protected $GroupName = 'MacOS';

    protected $g0 = 'File';

    protected $g1 = 'MacOS';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'MD Item EXIF GPS Version';

    protected $local_g2 = 'Location';

}
