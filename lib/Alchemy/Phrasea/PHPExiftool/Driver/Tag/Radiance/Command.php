<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Radiance;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Command extends AbstractTag
{

    protected $Id = '_command';

    protected $Name = 'Command';

    protected $FullName = 'Radiance::Main';

    protected $GroupName = 'Radiance';

    protected $g0 = 'Radiance';

    protected $g1 = 'Radiance';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Command';

}
