<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Track;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class TrackModifyDate extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'TrackModifyDate';

    protected $FullName = 'QuickTime::TrackHeader';

    protected $GroupName = 'Track#';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Track#';

    protected $g2 = 'Video';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Track Modify Date';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

}
