<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RAF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RelativeExposure extends AbstractTag
{

    protected $Id = 37376;

    protected $Name = 'RelativeExposure';

    protected $FullName = 'FujiFilm::RAF';

    protected $GroupName = 'RAF';

    protected $g0 = 'RAF';

    protected $g1 = 'RAF';

    protected $g2 = 'Image';

    protected $Type = 'rational32s';

    protected $Writable = false;

    protected $Description = 'Relative Exposure';

}
