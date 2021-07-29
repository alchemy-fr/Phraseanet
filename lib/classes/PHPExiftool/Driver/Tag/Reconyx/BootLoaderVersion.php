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
class BootLoaderVersion extends AbstractTag
{

    protected $Id = 38;

    protected $Name = 'BootLoaderVersion';

    protected $FullName = 'Reconyx::Type2';

    protected $GroupName = 'Reconyx';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Reconyx';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Boot Loader Version';

    protected $flag_Permanent = true;

    protected $MaxLength = 7;

}
