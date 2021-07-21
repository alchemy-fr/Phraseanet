<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Audible;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ITunesMediaType extends AbstractTag
{

    protected $Id = 'itunesmediatype';

    protected $Name = 'iTunesMediaType';

    protected $FullName = 'Audible::meta';

    protected $GroupName = 'Audible';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Audible';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'iTunes Media Type';

}
