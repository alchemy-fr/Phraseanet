<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Meta;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PrimaryItemReference extends AbstractTag
{

    protected $Id = 'pitm';

    protected $Name = 'PrimaryItemReference';

    protected $FullName = 'QuickTime::Meta';

    protected $GroupName = 'Meta';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Meta';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Primary Item Reference';

    protected $Index = 'mixed';

}
