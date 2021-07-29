<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Meta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Free extends AbstractTag
{

    protected $Id = 'free';

    protected $Name = 'Free';

    protected $FullName = 'QuickTime::Meta';

    protected $GroupName = 'Meta';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Meta';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Free';

    protected $flag_Binary = true;

}
