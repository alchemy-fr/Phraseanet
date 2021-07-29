<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Reconyx;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Micro2Version extends AbstractTag
{

    protected $Id = 45;

    protected $Name = 'Micro2Version';

    protected $FullName = 'Reconyx::Type2';

    protected $GroupName = 'Reconyx';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Reconyx';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Micro 2 Version';

    protected $flag_Permanent = true;

    protected $MaxLength = 7;

}
