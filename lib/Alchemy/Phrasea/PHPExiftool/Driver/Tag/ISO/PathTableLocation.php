<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ISO;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PathTableLocation extends AbstractTag
{

    protected $Id = 140;

    protected $Name = 'PathTableLocation';

    protected $FullName = 'ISO::PrimaryVolume';

    protected $GroupName = 'ISO';

    protected $g0 = 'ISO';

    protected $g1 = 'ISO';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Path Table Location';

}
