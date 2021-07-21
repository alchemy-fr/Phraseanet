<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Lytro;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Model extends AbstractTag
{

    protected $Id = 'CameraModel';

    protected $Name = 'Model';

    protected $FullName = 'Lytro::Main';

    protected $GroupName = 'Lytro';

    protected $g0 = 'Lytro';

    protected $g1 = 'Lytro';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Camera Model Name';

}
