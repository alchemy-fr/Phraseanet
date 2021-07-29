<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Red;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CameraOperator extends AbstractTag
{

    protected $Id = 4220;

    protected $Name = 'CameraOperator';

    protected $FullName = 'Red::Main';

    protected $GroupName = 'Red';

    protected $g0 = 'Red';

    protected $g1 = 'Red';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Camera Operator';

    protected $local_g2 = 'Author';

}
