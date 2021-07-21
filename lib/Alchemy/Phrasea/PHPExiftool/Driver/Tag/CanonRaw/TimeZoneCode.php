<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\CanonRaw;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class TimeZoneCode extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'TimeZoneCode';

    protected $FullName = 'CanonRaw::TimeStamp';

    protected $GroupName = 'CanonRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonRaw';

    protected $g2 = 'Time';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Time Zone Code';

    protected $flag_Permanent = true;

}
