<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\UserData;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Copyright extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Copyright';

    protected $FullName = 'QuickTime::UserData';

    protected $GroupName = 'UserData';

    protected $g0 = 'QuickTime';

    protected $g1 = 'UserData';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Copyright';

    protected $local_g2 = 'Author';

    protected $flag_Avoid = true;

}
