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
class NullMediaHeader extends AbstractTag
{

    protected $Id = 'nmhd';

    protected $Name = 'NullMediaHeader';

    protected $FullName = 'QuickTime::MediaInfo';

    protected $GroupName = 'Track#';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Track#';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Null Media Header';

    protected $flag_Binary = true;

}
