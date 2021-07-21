<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\QuickTime;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class TrackProperty extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'TrackProperty';

    protected $FullName = 'QuickTime::MetaData';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Track Property';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No presentation',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Main track',
        ),
    );

}
