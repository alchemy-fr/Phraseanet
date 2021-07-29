<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FITS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Background extends AbstractTag
{

    protected $Id = 'BACKGRND';

    protected $Name = 'Background';

    protected $FullName = 'FITS::Main';

    protected $GroupName = 'FITS';

    protected $g0 = 'FITS';

    protected $g1 = 'FITS';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Background';

}
