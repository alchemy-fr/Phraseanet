<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MakerNotes;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Firmware extends AbstractTag
{

    protected $Id = 26;

    protected $Name = 'Firmware';

    protected $FullName = 'QuickTime::INSV_MakerNotes';

    protected $GroupName = 'MakerNotes';

    protected $g0 = 'QuickTime';

    protected $g1 = 'MakerNotes';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Firmware';

}
