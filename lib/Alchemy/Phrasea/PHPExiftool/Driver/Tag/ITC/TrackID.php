<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ITC;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class TrackID extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'TrackID';

    protected $FullName = 'ITC::Item';

    protected $GroupName = 'ITC';

    protected $g0 = 'ITC';

    protected $g1 = 'ITC';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Track ID';

    protected $MaxLength = 8;

}
