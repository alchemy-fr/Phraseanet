<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\GIMP;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Tattoo extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'Tattoo';

    protected $FullName = 'GIMP::Main';

    protected $GroupName = 'GIMP';

    protected $g0 = 'GIMP';

    protected $g1 = 'GIMP';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Tattoo';

}
