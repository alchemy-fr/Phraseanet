<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Motorola;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BuildNumber extends AbstractTag
{

    protected $Id = 21760;

    protected $Name = 'BuildNumber';

    protected $FullName = 'Motorola::Main';

    protected $GroupName = 'Motorola';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Motorola';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Build Number';

    protected $flag_Permanent = true;

}
