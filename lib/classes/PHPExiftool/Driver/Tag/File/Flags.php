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
class Flags extends AbstractTag
{

    protected $Id = '4.4';

    protected $Name = 'Flags';

    protected $FullName = 'BPG::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Flags';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Animation',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Limited Range',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Extension Present',
        ),
    );

}
