<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Apple;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AccelerationVector extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'AccelerationVector';

    protected $FullName = 'Apple::Main';

    protected $GroupName = 'Apple';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Apple';

    protected $g2 = 'Image';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Acceleration Vector';

    protected $local_g2 = 'Camera';

    protected $flag_Permanent = true;

    protected $MaxLength = 3;

}
