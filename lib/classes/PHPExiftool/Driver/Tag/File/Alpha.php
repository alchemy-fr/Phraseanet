<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Alpha extends AbstractTag
{

    protected $Id = '4.1';

    protected $Name = 'Alpha';

    protected $FullName = 'BPG::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Alpha';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Alpha Plane',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Alpha Exists (W color component)',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'Alpha Exists (color not premultiplied)',
        ),
        4100 => array(
            'Id' => 4100,
            'Label' => 'Alpha Exists (color premultiplied)',
        ),
    );

}
