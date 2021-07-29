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
class SpeedZ extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'SpeedZ';

    protected $FullName = 'DJI::Main';

    protected $GroupName = 'DJI';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'DJI';

    protected $g2 = 'Camera';

    protected $Type = 'float';

    protected $Writable = true;

    protected $Description = 'Speed Z';

    protected $flag_Permanent = true;

}
