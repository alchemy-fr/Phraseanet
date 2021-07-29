<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\DJI;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Make extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'Make';

    protected $FullName = 'DJI::Main';

    protected $GroupName = 'DJI';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'DJI';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Make';

    protected $flag_Permanent = true;

}
