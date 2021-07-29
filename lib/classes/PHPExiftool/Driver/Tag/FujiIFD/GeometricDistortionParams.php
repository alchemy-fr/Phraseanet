<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GeometricDistortionParams extends AbstractTag
{

    protected $Id = 61451;

    protected $Name = 'GeometricDistortionParams';

    protected $FullName = 'FujiFilm::IFD';

    protected $GroupName = 'FujiIFD';

    protected $g0 = 'RAF';

    protected $g1 = 'FujiIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Geometric Distortion Params';

}
