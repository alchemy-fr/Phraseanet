<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GIMP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Units extends AbstractTag
{

    protected $Id = 22;

    protected $Name = 'Units';

    protected $FullName = 'GIMP::Main';

    protected $GroupName = 'GIMP';

    protected $g0 = 'GIMP';

    protected $g1 = 'GIMP';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Units';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Inches',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'mm',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Points',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Picas',
        ),
    );

}
