<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Photoshop;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PrintStyle extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PrintStyle';

    protected $FullName = 'mixed';

    protected $GroupName = 'Photoshop';

    protected $g0 = 'Photoshop';

    protected $g1 = 'Photoshop';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Print Style';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Centered',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Size to Fit',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'User Defined',
        ),
    );

}
