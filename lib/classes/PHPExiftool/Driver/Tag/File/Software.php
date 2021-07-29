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
class Software extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'Software';

    protected $FullName = 'PCX::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Software';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'PC Paintbrush 2.5',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'PC Paintbrush 2.8 (with palette)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'PC Paintbrush 2.8 (without palette)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'PC Paintbrush for Windows',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'PC Paintbrush 3.0+',
        ),
    );

}
